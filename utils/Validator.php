<?php
// utils/Validator.php

class Validator {
    
    public static function validateInvoiceInput($data) {
        $errors = [];

        // Type
        if (empty($data['type']) || !in_array($data['type'], ['INITIAL', 'RECTIFICATIVA'])) {
            $errors[] = "Campo 'type' es inválido o falta. Valores permitidos: INITIAL, RECTIFICATIVA.";
        }

        // Amount (Allow negative ONLY for RECTIFICATIVA)
        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
             $errors[] = "Campo 'amount' es requerido y debe ser un número.";
        } else {
            $amount = (float)$data['amount'];
            if ($data['type'] === 'INITIAL' && $amount < 0) {
                $errors[] = "Las facturas originales (INITIAL) deben tener un importe positivo.";
            }
        }

        // Concept
        if (empty($data['concept'])) {
            $errors[] = "Campo 'concept' es requerido.";
        } elseif (strlen($data['concept']) < 5) {
             $errors[] = "El concepto debe tener al menos 5 caracteres.";
        }

        // Recipient NIF (Regex Strict)
        if (!empty($data['recipientNIF'])) {
            if (!self::isValidNif($data['recipientNIF'])) {
                $errors[] = "El NIF/CIF del receptor no tiene un formato válido.";
            }
        }

        // Specific Logic for Rectificativa
        if (($data['type'] ?? '') === 'RECTIFICATIVA') {
            if (empty($data['originalInvoiceId'])) {
                $errors[] = "Facturas RECTIFICATIVA requieren 'originalInvoiceId'.";
            }
            if (empty($data['reason'])) {
                 // Mapeamos 'reason' del input original o 'rectificationReason'
                 if (empty($data['rectificationReason'])) {
                     $errors[] = "Facturas RECTIFICATIVA requieren 'reason' o 'rectificationReason'.";
                 }
            }
        }

        return $errors;
    }

    private static function isValidNif($nif) {
        $nif = strtoupper(trim($nif));
        // Regex para DNI/NIE/CIF básicos (Simplificado para ejemplo: Letra+Nums+Letra ó 8Nums+Letra)
        // Acepta: 12345678Z, X12345678Z, A12345678
        return preg_match('/^[A-Z0-9]{9}$/', $nif);
    }
}
