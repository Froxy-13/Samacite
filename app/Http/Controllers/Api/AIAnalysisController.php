<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AIAnalysisController extends BaseController
{
    /**
     * Analyser une image de signalement avec Google Gemini Vision
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeImage(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Image invalide');
        }

        try {
            // 1. Récupérer l'image
            $image = $request->file('image');
            $imagePath = $image->getRealPath();
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = $image->getMimeType();

            // 2. Récupérer la clé API
            $apiKey = config('services.gemini.api_key');
            
            if (!$apiKey) {
                return $this->error('Clé API Gemini non configurée', 500);
            }

            // 3. Préparer le prompt
            $prompt = "Analyse cette photo d'un problème urbain et génère un JSON avec:
- title: titre court et descriptif (max 60 caractères)
- description: description détaillée du problème (80-200 caractères)
- category_name: catégorie parmi (nid_de_poule, ordures, eclairage, graffitis, infrastructure, autre)
- confidence: score de confiance 0.0-1.0

Concentre-toi sur le type de problème, la gravité et les éléments visibles.
Réponds UNIQUEMENT avec le JSON, sans texte supplémentaire.

Exemple de réponse:
{
  \"title\": \"Nid de poule avenue principale\",
  \"description\": \"Important trou dans la chaussée présentant un danger. Profondeur estimée 15cm.\",
  \"category_name\": \"nid_de_poule\",
  \"confidence\": 0.95
}";

            // 4. Appeler l'API Gemini
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2048, // Augmenté pour éviter les erreurs MAX_TOKENS
                    'topK' => 40,
                    'topP' => 0.95,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_NONE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_NONE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_NONE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_NONE'
                    ]
                ]
            ]);

            if (!$response->successful()) {
                Log::error('Gemini API Error: ' . $response->body());
                return $this->error('Erreur lors de l\'appel à l\'API Gemini', 500);
            }

            $responseData = $response->json();
            
            // Logger la réponse brute pour debug
            Log::info('Gemini Raw Response: ' . json_encode($responseData));

            // 5. Vérifier finishReason avant de parser
            $finishReason = $responseData['candidates'][0]['finishReason'] ?? null;
            
            switch ($finishReason) {
                case 'STOP':
                    // Réponse complète et normale - continuer
                    break;
                    
                case 'MAX_TOKENS':
                    Log::warning('Gemini MAX_TOKENS reached', ['data' => $responseData]);
                    return $this->error('La réponse IA a été tronquée. Veuillez réessayer avec une image plus simple.', 500);
                    
                case 'SAFETY':
                    Log::warning('Gemini SAFETY filter triggered', ['data' => $responseData]);
                    return $this->error('L\'image a été bloquée par les filtres de sécurité.', 400);
                    
                case 'RECITATION':
                    Log::warning('Gemini RECITATION detected', ['data' => $responseData]);
                    return $this->error('Contenu protégé par des droits d\'auteur détecté.', 400);
                    
                default:
                    Log::error('Unexpected finishReason', [
                        'reason' => $finishReason,
                        'data' => $responseData
                    ]);
                    return $this->error("Erreur IA inattendue (code: {$finishReason})", 500);
            }

            // 6. Parser la réponse - Gérer les différents formats (Gemini 1.5 et 2.0+)
            $resultText = null;
            
            // Format Gemini 1.5 : candidates[0].content.parts[0].text
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $resultText = $responseData['candidates'][0]['content']['parts'][0]['text'];
            }
            // Format Gemini 2.0+ : candidates[0].text (structure simplifiée)
            elseif (isset($responseData['candidates'][0]['text'])) {
                $resultText = $responseData['candidates'][0]['text'];
            }
            // Format alternatif : text direct
            elseif (isset($responseData['text'])) {
                $resultText = $responseData['text'];
            }
            
            if (!$resultText) {
                Log::error('No text found in Gemini response structure: ' . json_encode($responseData));
                return $this->error('Format de réponse invalide de l\'IA', 500);
            }
            
            // 7. Extraire le JSON de la réponse
            $resultText = trim($resultText);
            
            // Extraire le JSON s'il est entouré de ```json```
            if (preg_match('/```json\s*(.*?)\s*```/s', $resultText, $matches)) {
                $resultText = $matches[1];
            } else {
                // Nettoyer les marqueurs markdown si présents
                $resultText = preg_replace('/^```json\s*|\s*```$/m', '', $resultText);
            }
            
            Log::info('Extracted text from Gemini: ' . $resultText);
            
            $aiResult = json_decode($resultText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON parsing error: ' . json_last_error_msg() . '. Text was: ' . $resultText);
                return $this->error('Impossible de parser la réponse JSON de l\'IA', 500);
            }

            if (!$aiResult || !isset($aiResult['title'])) {
                Log::error('Invalid AI Response - Missing required fields. Result was: ' . json_encode($aiResult));
                return $this->error('Format de réponse invalide de l\'IA', 500);
            }

            // 8. Mapper catégorie vers ID
            $categoryMap = [
                'nid_de_poule' => 1,
                'ordures' => 2,
                'eclairage' => 3,
                'graffitis' => 4,
                'infrastructure' => 5,
                'autre' => 6,
            ];

            $categoryName = strtolower($aiResult['category_name'] ?? 'autre');
            $categoryId = $categoryMap[$categoryName] ?? 6;

            // 9. Retourner le résultat
            return $this->success([
                'title' => $aiResult['title'],
                'description' => $aiResult['description'],
                'category_id' => $categoryId,
                'confidence' => $aiResult['confidence'] ?? 0.8,
                'ai_metadata' => [
                    'finish_reason' => $finishReason,
                    'tokens_used' => $responseData['usageMetadata']['totalTokenCount'] ?? null,
                ]
            ], 'Image analysée avec succès');

        } catch (\Exception $e) {
            Log::error('AI Analysis Error: ' . $e->getMessage());
            
            return $this->error(
                'Erreur lors de l\'analyse de l\'image',
                500,
                config('app.debug') ? ['error' => $e->getMessage()] : null
            );
        }
    }
}
