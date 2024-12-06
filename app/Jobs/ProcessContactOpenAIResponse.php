<?php

namespace App\Jobs;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessContactOpenAIResponse implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $contact;
    protected $messageContent;

    /**
     * Create a new job instance.
     *
     * @param Contact $contact
     * @param string $messageContent
     */
    public function __construct(Contact $contact, $messageContent)
    {
         \Log::info("Job initialized with contact ID: " . $contact->id . " and message content: " . $messageContent);
        $this->contact = $contact;
        $this->messageContent = $messageContent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('Inside handle method of ProcessContactOpenAIResponse');

        // Check if OpenAI is enabled for the contact
        if (!$this->contact->openai_enabled) {
            \Log::info("openai enalbed is : " . $this->contact->openai_enabled);
            return; // Skip if OpenAI is disabled for this contact
        }

        try {
            \Log::info("Processing OpenAI response for contact: " . $this->contact->id);

            // Prepare OpenAI API request
            $openai_api_key = env('OPENAI_API_KEY');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openai_api_key,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo', // Or 'gpt-4' if you have access
                'messages' => [
                    ['role' => 'user', 'content' => $this->messageContent],
                ],
                'max_tokens' => 150, // Limit tokens to prevent excessive responses
            ]);

            // Check if the OpenAI API call was successful
            if ($response->successful()) {
                $responseData = $response->json();

                // Ensure 'choices' exists in the response
                if (isset($responseData['choices'][0]['message']['content'])) {
                    $openaiResponse = $responseData['choices'][0]['message']['content'];

                    // Send the OpenAI response as a message back to the chat
                    // We can also generate a unique message ID or use the original one from OpenAI
                    $this->contact->sendMessage($openaiResponse, true, false, "TEXT", uniqid('openai-response-'));
                } else {
                    // In case OpenAI response format is not as expected
                    $this->contact->sendMessage(
                        "Sorry, I couldn't process your message at the moment. Please try again later.",
                        true,
                        false,
                        "TEXT",
                        "openai-error-format"
                    );
                }
            } else {
                // Handle OpenAI API failure (e.g., rate-limited, API error, etc.)
                $this->contact->sendMessage(
                    "An error occurred while processing your message with OpenAI. Please try again later.",
                    true,
                    false,
                    "TEXT",
                    "openai-error-api"
                );
            }
        } catch (\Throwable $th) {
            // Handle any unexpected errors or exceptions gracefully
             \Log::error("Error processing OpenAI response: " . $th->getMessage());
            $this->contact->sendMessage(
                "An unexpected error occurred while processing your message. Please try again later.",
                true,
                false,
                "TEXT",
                "openai-error-exception"
            );
        }
    }
}
