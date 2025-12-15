<?php
// tests/ValidatorTest.php
require_once __DIR__ . '/../utils/Validator.php';

function testValidator() {
    echo "\nRunning Validator Tests...\n";
    
    // Case 1: Valid Input (INITIAL)
    $validData = [
        'type' => 'INITIAL',
        'amount' => 100,
        'concept' => 'Test',
    ];
    $errors = Validator::validateInvoiceInput($validData);
    echo (empty($errors) ? "PASS" : "FAIL") . ": Valid Input\n";
    if (!empty($errors)) print_r($errors);

    // Case 2: Missing Type
    $invalidData1 = ['amount' => 100];
    $errors1 = Validator::validateInvoiceInput($invalidData1);
    echo (!empty($errors1) ? "PASS" : "FAIL") . ": Missing Type Detected\n";

    // Case 3: Invalid Amount
    $invalidData2 = ['type' => 'INITIAL', 'amount' => 'abc', 'concept' => 'Test'];
    $errors2 = Validator::validateInvoiceInput($invalidData2);
    echo (!empty($errors2) ? "PASS" : "FAIL") . ": Invalid Amount Detected\n";

    // Case 4: Rectificativa missing ID
    $invalidData3 = ['type' => 'RECTIFICATIVA', 'amount' => 100, 'concept' => 'Test'];
    $errors3 = Validator::validateInvoiceInput($invalidData3);
    echo (in_array("Facturas RECTIFICATIVA requieren 'originalInvoiceId'.", $errors3) ? "PASS" : "FAIL") . ": Missing Original ID for Rectificativa\n";
}

testValidator();
?>
