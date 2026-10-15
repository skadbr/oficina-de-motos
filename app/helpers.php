<?php
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function moneyToCents($value)
{
    $value = trim((string) $value);
    if (strpos($value, ',') !== false) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }
    if ($value === '' || !is_numeric($value) || (float) $value < 0) {
        throw new InvalidArgumentException('Informe um valor válido e não negativo.');
    }
    return (int) round((float) $value * 100);
}

function money($cents)
{
    return 'R$ ' . number_format(((int) $cents) / 100, 2, ',', '.');
}

function requiredText($value, $label)
{
    $value = trim((string) $value);
    if ($value === '') {
        throw new InvalidArgumentException('Preencha o campo ' . $label . '.');
    }
    return $value;
}

function validateLocalDate($value, $label)
{
    $date = DateTime::createFromFormat('Y-m-d\\TH:i', $value);
    $errors = DateTime::getLastErrors();
    if (!$date || $date->format('Y-m-d\\TH:i') !== $value || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
        throw new InvalidArgumentException('Informe uma data válida para ' . $label . '.');
    }
    return $value;
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}