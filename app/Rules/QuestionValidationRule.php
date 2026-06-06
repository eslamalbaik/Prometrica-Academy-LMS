<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class QuestionValidationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The options must be a valid list.');
            return;
        }

        if (count($value) < 2) {
            $fail('Each question must have at least 2 options.');
            return;
        }

        $correctCount = 0;
        foreach ($value as $index => $option) {
            if (!is_array($option) || empty(trim($option['option_text'] ?? ''))) {
                $fail("Option " . ($index + 1) . " text is required.");
                return;
            }

            $isCorrect = filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($isCorrect) {
                $correctCount++;
            }
        }

        if ($correctCount !== 1) {
            $fail('Each question must have exactly one correct answer.');
        }
    }
}
