<?php

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    
    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }
    
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }
    
    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $value = $this->data[$field] ?? null;
            
            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    private function applyRule(string $field, mixed $value, string $rule): void
    {
        if (str_starts_with($rule, 'required')) {
            if ($value === null || $value === '') {
                $this->addError($field, "The $field field is required.");
            }
            return;
        }
        
        if ($value === null || $value === '') {
            return;
        }
        
        if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The $field must be a valid email address.");
            return;
        }
        
        if ($rule === 'numeric' && !is_numeric($value)) {
            $this->addError($field, "The $field must be numeric.");
            return;
        }
        
        if ($rule === 'integer' && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, "The $field must be an integer.");
            return;
        }
        
        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $this->addError($field, "The $field must be at least $min characters.");
            }
            return;
        }
        
        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                $this->addError($field, "The $field must not exceed $max characters.");
            }
            return;
        }
        
        if (str_starts_with($rule, 'in:')) {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array($value, $allowed, true)) {
                $this->addError($field, "The $field must be one of: " . implode(', ', $allowed) . ".");
            }
            return;
        }
        
        if ($rule === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "The $field must be a valid URL.");
            return;
        }
        
        if ($rule === 'date' && strtotime($value) === false) {
            $this->addError($field, "The $field must be a valid date.");
            return;
        }
    }
    
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    public function errors(): array
    {
        return $this->errors;
    }
    
    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }
        return null;
    }
    
    public function fails(): bool
    {
        return !empty($this->errors);
    }
}
