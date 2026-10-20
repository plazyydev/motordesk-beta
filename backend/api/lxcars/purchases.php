<?php
// backend/api/lxcars/purchases.php

/**
 * Ankauf & Kalkulation fuer den Fahrzeugverkauf
 *
 * Alle Berechnungen (Gesamtkosten, Zielpreis, Gewinn, Standzeit) liegen in der
 * Datenbankfunktion car_purchases_calc().
 */

/**
 * Laedt die Ankauftabelle inkl. berechneter Werte
 *
 * @param string $data['search'] Suchtext (Kennzeichen, Modell, FIN, Verkaeufer)
 * @param string $data['status'] Statusfilter (purchased, listed, reserved, sold) oder leer
 * @testdata {"search": "", "status": ""}
 */
function getCarPurchases($data) {
    $db = DbhCompany::begin();
    $row = $db->getOne(
        "SELECT car_purchases_calc(:search, :status) AS result",
        [':search' => trim($data['search'] ?? ''), ':status' => trim($data['status'] ?? '')]
    );
    resultInfo(true, '', json_decode($row['result'], true));
}

/**
 * Speichert einen Ankauf (Anlegen oder Aktualisieren) und protokolliert Preisaenderungen
 *
 * @param int $data['c_id'] Fahrzeug-ID
 * @param float $data['purchase_price'] Einkaufspreis
 * @param float $data['asking_price'] Aktueller Angebotspreis
 * @testdata {"c_id": 1, "purchase_price": 4500, "costs_repair": 300, "desired_margin_percent": 12, "asking_price": 5900, "status": "listed"}
 */
function saveCarPurchase($data) {
    if (empty($data['c_id'])) {
        resultInfo(false, 'VALIDATION_ERROR', 'Fahrzeug fehlt');
        return;
    }
    $db = DbhCompany::begin();
    $row = $db->getOne("SELECT car_purchase_save(CAST(:p AS jsonb)) AS result", [':p' => json_encode($data)]);
    resultInfo(true, '', json_decode($row['result'], true));
}
