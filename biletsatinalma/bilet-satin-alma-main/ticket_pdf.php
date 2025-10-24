<?php
session_start();
require_once __DIR__ . '/fpdf/fpdf.php';
$db = new PDO("sqlite:bilet_sistemi.db");

if (!isset($_GET['id'])) {
    die("Bilet bulunamadı");
}

$ticket_id = (int) $_GET['id'];

// Bilet bilgilerini al
$stmt = $db->prepare("SELECT tk.id AS ticket_id, 
                             tr.price, tr.departure_city, tr.destination_city, 
                             tr.departure_time, tr.arrival_time, 
                             b.name AS company_name, 
                             u.full_name
                      FROM Tickets tk
                      JOIN Trips tr ON tk.trip_id = tr.id
                      JOIN Bus_Company b ON tr.company_id = b.id
                      JOIN User u ON tk.user_id = u.id
                      WHERE tk.id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Bilet bulunamadı");
}

$pdf = new FPDF();
$pdf->AddPage();

// Başlık
$pdf->SetFont('Arial','B',18);
$pdf->SetTextColor(42, 77, 74); // koyu yeşil
$pdf->Cell(0,12,'Otobus Bileti',0,1,'C');
$pdf->Ln(5);

// Alt çizgi
$pdf->SetDrawColor(42, 157, 143);
$pdf->SetLineWidth(0.7);
$pdf->Line(10, 28, 200, 28);
$pdf->Ln(10);

// Yolcu bilgileri kutusu
$pdf->SetFont('Arial','B',12);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(40,10,'Bilet ID:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$ticket['ticket_id'],0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Yolcu:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$ticket['full_name'],0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Firma:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$ticket['company_name'],0,1);

$pdf->Ln(5);

// Rota bilgileri kutusu
$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Kalkis:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$ticket['departure_city'].' - '.$ticket['departure_time'],0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Varis:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$ticket['destination_city'].' - '.$ticket['arrival_time'],0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Fiyat:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$ticket['price'].' TL',0,1);

$pdf->Ln(15);

// Alt bilgi
$pdf->SetFont('Arial','I',10);
$pdf->SetTextColor(100,100,100);
$pdf->Cell(0,10,'Güvenli yolculuklar dileriz - Otobüs Bilet Sistemi',0,1,'C');

$pdf->Output('I','bilet_'.$ticket['ticket_id'].'.pdf');