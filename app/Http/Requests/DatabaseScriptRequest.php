<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DatabaseScriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'script' => [
                'required',
                'file',
                'max:2048',
            ],
        ];
    }

    /**
     * Validation complémentaire : extension .sql imposée, mime d'un fichier
     * texte (les navigateurs rapportent rarement un mime fiable pour .sql)
     * et contenu lisible.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $file = $this->file('script');

            if (! $file) {
                return;
            }

            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension !== 'sql') {
                $validator->errors()->add('script', 'Seuls les fichiers .sql sont acceptés.');

                return;
            }

            $mime = $file->getMimeType();
            $allowedMimes = [
                'text/plain',
                'application/sql',
                'text/x-sql',
                'application/x-sql',
                'application/octet-stream',
                'application/x-empty',
                'text/x-plain',
            ];

            if ($mime !== null && ! in_array($mime, $allowedMimes, true)) {
                $validator->errors()->add('script', 'Type de fichier non autorisé (un script SQL texte est attendu).');

                return;
            }

            try {
                $file->getContent();
            } catch (\Throwable) {
                $validator->errors()->add('script', 'Le fichier est illisible.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'script.required' => 'Veuillez sélectionner un fichier SQL.',
            'script.file' => 'Le fichier fourni est invalide.',
            'script.max' => 'Le fichier ne doit pas dépasser 2 Mo.',
        ];
    }
}
