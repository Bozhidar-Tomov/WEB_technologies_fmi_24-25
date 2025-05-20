<?php
header('Content-Type: application/json; charset=utf-8');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    $data = $_POST;
}

function validateTextField($field, $label, $minLength, $maxLength, &$errors) {
    if (!isset($field) || mb_strlen(trim($field)) === 0) {
        $errors[$label] = "$label е задължително поле";
    } else {
        $length = mb_strlen($field);
        if ($length < $minLength || $length > $maxLength) {
            $errors[$label] = "$label трябва да е между $minLength и $maxLength символа, а вие сте въвели $length";
        }
    }
}

$errors = [];

validateTextField($data['name'] ?? null, 'name', 2, 150, $errors);

validateTextField($data['teacher'] ?? null, 'teacher', 3, 200, $errors);

if (!isset($data['description']) || mb_strlen(trim($data['description'])) === 0) {
    $errors['description'] = "Описанието е задължително поле";
} else {
    $len = mb_strlen($data['description']);
    if ($len < 10) {
        $errors['description'] = "Описанието трябва да е с дължина поне 10 символа, а вие сте въвели $len";
    }
}

$validGroups = ['М', 'ПМ', 'ОКН', 'ЯКН'];
if (!isset($data['group']) || mb_strlen(trim($data['group'])) === 0) {
    $errors['group'] = "Групата е задължително поле";
} else if (!in_array($data['group'], $validGroups)) {
    $errors['group'] = "Невалидна група, изберете една от М, ПМ, ОКН и ЯКН";
}

if (!isset($data['credits'])) {
    $errors['credits'] = "Кредитите са задължително поле";
} else if (!ctype_digit(strval($data['credits'])) || intval($data['credits']) <= 0) {
    $errors['credits'] = "Кредитите трябва да са цяло положително число";
}

if (empty($errors)) {
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
}
?>