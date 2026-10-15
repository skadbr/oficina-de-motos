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

function parseBrazilianDate($value, $label)
{
    $value = trim((string) $value);
    $date = DateTime::createFromFormat('!d/m/Y', $value);
    $errors = DateTime::getLastErrors();
    if (!$date || $date->format('d/m/Y') !== $value || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
        throw new InvalidArgumentException('Informe uma data válida para ' . $label . '.');
    }
    return $date->format('Y-m-d');
}

function validateBrazilianDateTime($dateValue, $timeValue, $label)
{
    $date = parseBrazilianDate($dateValue, $label);
    $time = trim((string) $timeValue);
    if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $time)) {
        throw new InvalidArgumentException('Informe um horário válido para ' . $label . '.');
    }
    return $date . 'T' . $time;
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}