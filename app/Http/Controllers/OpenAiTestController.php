<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI;

class OpenAiTestController extends Controller
{
    public function test(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        // Fetch the message from the request
        $userMessage = $validated['message'];

        // Create a new OpenAI client
        $client = OpenAI::client(env('OPENAI_API_KEY')); // Ensure you have the OPENAI_API_KEY in your .env file

        // Use the OpenAI API to get a response
        $response = $client->chat()->create([
            'model' => 'gpt-3.5-turbo', // Adjust model if needed
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        // Extract the reply
        $aiResponse = $response->choices[0]->message->content;

        // Return the response
        return response()->json([
            'status' => true,
            'response' => $aiResponse,
        ]);
    }
}
