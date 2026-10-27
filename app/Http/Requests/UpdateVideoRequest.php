<?php

namespace App\Http\Requests;

use App\Models\Video;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $video = $this->route('video');

        return $user !== null && $video instanceof Video && $user->can('update', $video);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'caption' => ['required', 'string', 'max:200'],
            'video' => ['nullable', 'file', 'mimes:mp4,webm', 'max:20480'],
            'remove_video' => ['sometimes', 'boolean'],
        ];
    }
}
