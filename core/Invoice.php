<?php
/**
 * ClubCMS — Gestion des factures
 * Conservation légale : 10 ans
 * Numérotation : YYYY-NNNN (ex: 2025-0001)
 */
class Invoice {

    // Taux TVA disponibles
    public static function tvaRates(): array {
        return [
            '0'    => 'Exonéré (0%)',
            '2.1'  => 'Taux super-réduit (2,1%)',
            '5.5'  => 'Taux réduit (5,5%)',
            '10'   => 'Taux intermédiaire (10%)',
            '20'   => 'Taux normal (20%)',
        ];
    }

    /**
     * Générer le prochain numéro de facture YYYY-NNNN
     */
    public static function nextNumber(): string {
        $year = date('Y');
        try {
            $last = Database::one(
                "SELECT invoice_number FROM cc_invoices WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1",
                [$year . '-%']
            );
            if ($last) {
                $n = (int)explode('-', $last['invoice_number'])[1] + 1;
            } else {
                $n = 1;
            }
            return $year . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
        } catch(Exception $e) {
            return $year . '-' . str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Créer une facture pour une commande
     */
    public static function createForOrder(int $orderId): ?int {
        try {
            Database::run("CREATE TABLE IF NOT EXISTS cc_invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_number VARCHAR(30) NOT NULL UNIQUE,
                order_id INT NOT NULL, user_id INT DEFAULT NULL,
                status ENUM('draft','issued','paid','cancelled') DEFAULT 'issued',
                subtotal_ht DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                tva_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                tva_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                total_ttc DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                billing_info JSON DEFAULT NULL, items JSON NOT NULL,
                notes TEXT DEFAULT NULL, issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                paid_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Vérifier si facture existe déjà
            $existing = Database::one("SELECT id FROM cc_invoices WHERE order_id=?", [$orderId]);
            if ($existing) return (int)$existing['id'];

            $order   = Database::one(
                "SELECT o.*, u.firstname, u.lastname, u.email FROM cc_shop_orders o
                 LEFT JOIN cc_users u ON o.user_id=u.id WHERE o.id=?", [$orderId]
            );
            if (!$order) return null;

            $items      = json_decode($order['items'] ?? '[]', true) ?? [];
            $address    = json_decode($order['shipping_address'] ?? '{}', true) ?? [];
            $globalTva  = (float)Config::get('shop_tva_rate', '0');

            // Calculer TVA par article
            $subtotalHt  = 0;
            $tvaAmount   = 0;
            $invoiceItems= [];
            foreach ($items as $item) {
                // Chercher le taux TVA spécifique au produit
                $productTva = null;
                if (!empty($item['product_id'])) {
                    try {
                        $prod = Database::one("SELECT tva_rate, price_mode FROM cc_shop_products WHERE id=?", [$item['product_id']]);
                        if ($prod && $prod['tva_rate'] !== null) $productTva = (float)$prod['tva_rate'];
                    } catch(Exception $e) {}
                }
                $tvaRate = $productTva ?? $globalTva;
                $priceMode = Config::get('shop_price_mode', 'ttc');

                $priceTtc = (float)$item['price'];
                if ($priceMode === 'ht') {
                    $priceHt  = $priceTtc;
                    $priceTtc = $priceHt * (1 + $tvaRate / 100);
                } else {
                    $priceHt  = $tvaRate > 0 ? $priceTtc / (1 + $tvaRate / 100) : $priceTtc;
                }
                $lineHt  = round($priceHt * $item['qty'], 2);
                $lineTva = round(($priceTtc - $priceHt) * $item['qty'], 2);
                $subtotalHt += $lineHt;
                $tvaAmount  += $lineTva;
                $invoiceItems[] = array_merge($item, [
                    'price_ht'  => round($priceHt, 2),
                    'price_ttc' => round($priceTtc, 2),
                    'tva_rate'  => $tvaRate,
                    'tva_amount'=> $lineTva,
                    'total_ht'  => $lineHt,
                ]);
            }

            $billingInfo = [
                'name'    => trim(($address['firstname'] ?? $order['firstname'] ?? '') . ' ' . ($address['lastname'] ?? $order['lastname'] ?? '')),
                'email'   => $address['email'] ?? $order['email'] ?? '',
                'address' => $address['address'] ?? '',
                'city'    => $address['city'] ?? '',
                'zip'     => $address['zip'] ?? '',
                'country' => $address['country'] ?? 'France',
                'club'    => Config::get('club_name', ''),
                'siret'   => Config::get('shop_siret', ''),
            ];

            $invNumber = self::nextNumber();
            $invId = Database::insert(
                "INSERT INTO cc_invoices
                 (invoice_number,order_id,user_id,status,subtotal_ht,tva_rate,tva_amount,total_ttc,billing_info,items,issued_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,NOW())",
                [
                    $invNumber, $orderId, $order['user_id'] ?: null,
                    $order['status'] === 'paid' ? 'paid' : 'issued',
                    round($subtotalHt, 2), $globalTva,
                    round($tvaAmount, 2), round($order['total'], 2),
                    json_encode($billingInfo, JSON_UNESCAPED_UNICODE),
                    json_encode($invoiceItems, JSON_UNESCAPED_UNICODE),
                ]
            );

            ActivityLog::log('invoice_created', 'invoice', $invId, ['order_id' => $orderId, 'number' => $invNumber]);
            return $invId;
        } catch(Exception $e) {
            error_log('Invoice::createForOrder error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Générer le PDF en mémoire (retourne la string binaire)
     */
    public static function generatePdfString(int $invId): ?string {
        ob_start();
        self::generatePdf($invId, 'S');
        return ob_get_clean() ?: null;
    }

    /**
     * Générer le PDF d'une facture (FPDF)
     * $dest : 'D'=download, 'S'=string, 'I'=inline
     */
    public static function generatePdf(int $invId, string $dest = 'D'): void {
        $inv  = Database::one("SELECT * FROM cc_invoices WHERE id=?", [$invId]);
        if (!$inv) return;

        $bill  = json_decode($inv['billing_info'] ?? '{}', true) ?? [];
        $items = json_decode($inv['items'] ?? '[]', true) ?? [];
        $club  = Config::get('club_name', 'Club');
        $clubEmail = Config::get('club_email', '');
        $clubAddr  = Config::get('club_address', '');

        require_once CC_ROOT . '/pdf/fpdf/fpdf.php';
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(20, 15, 20);
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);

        // En-tête
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(29, 78, 216);
        $pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1//TRANSLIT', $club), 0, 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(100, 116, 139);
        if ($clubAddr) $pdf->Cell(0, 5, iconv('UTF-8','ISO-8859-1//TRANSLIT', $clubAddr), 0, 1);
        if ($clubEmail) $pdf->Cell(0, 5, $clubEmail, 0, 1);
        if (Config::get('shop_siret')) $pdf->Cell(0, 5, 'SIRET : '.Config::get('shop_siret'), 0, 1);

        $pdf->Ln(5);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(5);

        // Titre facture
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 10, 'FACTURE N° ' . $inv['invoice_number'], 0, 1);

        // Infos facture / client (2 colonnes)
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(71, 85, 105);
        $xLeft = 20; $xRight = 120;
        $yStart = $pdf->GetY();

        // Gauche : infos facture
        $pdf->SetXY($xLeft, $yStart);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(80, 6, 'DETAILS FACTURE', 0, 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($xLeft);
        $pdf->Cell(80, 5, 'Date : ' . date('d/m/Y', strtotime($inv['issued_at'])), 0, 1);
        $pdf->SetX($xLeft);
        $pdf->Cell(80, 5, 'Commande : #' . $inv['order_id'], 0, 1);
        $pdf->SetX($xLeft);
        $statusLabel = ['issued'=>'Emise','paid'=>'Payee','cancelled'=>'Annulee'][$inv['status']] ?? $inv['status'];
        $pdf->Cell(80, 5, 'Statut : ' . $statusLabel, 0, 1);

        // Droite : infos client
        $pdf->SetXY($xRight, $yStart);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(70, 6, 'FACTURER A', 0, 1);
        $pdf->SetFont('Arial', '', 9);
        foreach (['name','email','address','zip','city','country'] as $k) {
            if (!empty($bill[$k])) {
                $pdf->SetX($xRight);
                $pdf->Cell(70, 5, iconv('UTF-8','ISO-8859-1//TRANSLIT', $bill[$k]), 0, 1);
            }
        }

        $pdf->SetY(max($pdf->GetY(), $yStart + 35));
        $pdf->Ln(5);

        // Tableau des articles
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->Cell(75, 8, 'Article', 1, 0, 'L', true);
        $pdf->Cell(20, 8, 'Qte', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Prix HT', 1, 0, 'R', true);
        $pdf->Cell(20, 8, 'TVA', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Total TTC', 1, 1, 'R', true);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(15, 23, 42);
        $fill = false;
        foreach ($items as $item) {
            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
            $name = iconv('UTF-8','ISO-8859-1//TRANSLIT', mb_substr($item['name']??'',0,40));
            $pdf->Cell(75, 7, $name, 1, 0, 'L', true);
            $pdf->Cell(20, 7, (int)$item['qty'], 1, 0, 'C', true);
            $pdf->Cell(30, 7, number_format($item['price_ht']??0,2,',',' ').' EUR', 1, 0, 'R', true);
            $pdf->Cell(20, 7, ($item['tva_rate']??0).'%', 1, 0, 'C', true);
            $total = ($item['price_ttc']??$item['price']??0) * (int)$item['qty'];
            $pdf->Cell(25, 7, number_format($total,2,',',' ').' EUR', 1, 1, 'R', true);
            $fill = !$fill;
        }

        // Totaux
        $pdf->Ln(3);
        $pdf->SetX(120);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(45, 6, 'Sous-total HT :', 0, 0, 'R');
        $pdf->Cell(25, 6, number_format($inv['subtotal_ht'],2,',',' ').' EUR', 0, 1, 'R');
        $pdf->SetX(120);
        $pdf->Cell(45, 6, 'TVA ('.$inv['tva_rate'].'%) :', 0, 0, 'R');
        $pdf->Cell(25, 6, number_format($inv['tva_amount'],2,',',' ').' EUR', 0, 1, 'R');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(29, 78, 216);
        $pdf->SetX(120);
        $pdf->Cell(45, 8, 'TOTAL TTC :', 0, 0, 'R');
        $pdf->Cell(25, 8, number_format($inv['total_ttc'],2,',',' ').' EUR', 0, 1, 'R');

        // Pied de page
        $pdf->SetY(-30);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(2);
        $pdf->Cell(0, 5, iconv('UTF-8','ISO-8859-1//TRANSLIT',
            $club . ' — Document généré le ' . date('d/m/Y') . ' — Conserver 10 ans (obligation légale)'
        ), 0, 0, 'C');

        if ($dest === 'D') {
            while(ob_get_level()) ob_end_clean();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="facture-'.$inv['invoice_number'].'.pdf"');
        }
        $pdf->Output($dest, 'facture-'.$inv['invoice_number'].'.pdf');
        if ($dest === 'D') exit;
    }
}
