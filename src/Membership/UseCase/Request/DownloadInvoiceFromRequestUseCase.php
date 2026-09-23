<?php

namespace App\Membership\UseCase\Request;

use App\Core\Config\Parameters;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\UseCase\UseCaseBus;
use App\Core\UseCase\UseCaseInterface;
use Fpdf\Fpdf;

class DownloadInvoiceFromRequestUseCase implements UseCaseInterface
{
    private EntityRepositoryInterface $requestRepository;

    private EntityRepositoryInterface $campaignRepository;

    private UseCaseBus $useCaseBus;

    private Parameters $parameters;

    public function __construct(EntityManager $entityManager, UseCaseBus $useCaseBus, Parameters $parameters)
    {
        $this->requestRepository = $entityManager->getRepository('wolf-memberships.request');
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
        $this->useCaseBus = $useCaseBus;
        $this->parameters = $parameters;
    }

    public function execute(array $params = []): array
    {
        $campaignId = (int) ($params['campaign_id'] ?? 0);
        $requestId = (int) ($params['request_id'] ?? 0);
        $token = (string) ($params['token'] ?? '');

        if ($campaignId <= 0 || $requestId <= 0) {
            throw new \InvalidArgumentException('Campaign ID and request ID are required.');
        }

        $campaign = $this->campaignRepository->findById($campaignId);
        if (!$campaign) {
            throw new \RuntimeException('Campaign not found.');
        }

        $request = $this->requestRepository->findById($requestId);
        if (!$request) {
            throw new \RuntimeException('Request not found.');
        }

        if ((int) $request->campaign_id !== $campaignId) {
            throw new \RuntimeException('Request does not belong to this campaign.');
        }

        if ($request->status !== 'approved' && $request->status !== 'paid') {
            throw new \RuntimeException('Request is not eligible for invoice download.');
        }

        if ($token !== (string) $request->token) {
            throw new \RuntimeException('Invalid invoice download token.');
        }

        $content = $this->buildPdf($campaign, $request);
        $filename = sprintf('facture-%d-%d.pdf', $campaignId, $requestId);

        return [
            'content' => $content,
            'filename' => $filename,
            'mime_type' => 'application/pdf',
        ];
    }

    private function buildPdf(object $campaign, object $request): string
    {
        $totalAmountCents = (int) $request->total_amount;
        $lines = $this->extractInvoiceLines($request, $campaign, $totalAmountCents);
        $subTotalCents = (int) array_sum(array_map(static fn(array $line): int => (int) $line['total_cents'], $lines));

        $vatRate = (float) $this->parameters->get('billing.vat_rate', 0);
        $vatAmountCents = (int) round($subTotalCents * ($vatRate / 100));
        $grandTotalCents = $subTotalCents + $vatAmountCents;

        $pdf = new Fpdf();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $footerReservedSpace = 24;
        $pdf->SetAutoPageBreak(true, $footerReservedSpace);

        $logoPath = $this->resolveLogoPath();
        if ($logoPath !== null) {
            $pdf->Image($logoPath, 10, 10, 24);
        }

        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->Cell(0, 10, $this->pdfText('FACTURE'), 0, 1, 'R');

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(80, 86, 95);
        $pdf->Cell(0, 6, $this->pdfText('Numero: #' . (string) $request->id), 0, 1, 'R');
        $pdf->Cell(0, 6, $this->pdfText('Date: ' . date('Y-m-d H:i:s')), 0, 1, 'R');

        $pdf->Ln(6);

        $pdf->SetDrawColor(220, 226, 232);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(6);

        $companyName = (string) $this->parameters->get('invoice.company_name', 'Association');
        $companyEmail = (string) $this->parameters->get('invoice.company_email', '');
        $memberName = trim((string) ($request->firstname ?? '') . ' ' . (string) ($request->lastname ?? ''));

        $leftY = $pdf->GetY();
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->Cell(95, 6, $this->pdfText('EMETTEUR'), 0, 0, 'L');
        $pdf->Cell(95, 6, $this->pdfText('PAYEUR'), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(80, 86, 95);
        $pdf->Cell(95, 6, $this->pdfText($companyName), 0, 0, 'L');
        $pdf->Cell(95, 6, $this->pdfText($memberName), 0, 1, 'L');
        $pdf->Cell(95, 6, $this->pdfText($companyEmail), 0, 0, 'L');
        $pdf->Cell(95, 6, $this->pdfText((string) ($request->email ?? '')), 0, 1, 'L');

        $pdf->Ln(8);

        $pdf->SetFillColor(245, 247, 250);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(90, 8, $this->pdfText('Description'), 1, 0, 'L', true);
        $pdf->Cell(20, 8, $this->pdfText('Qte'), 1, 0, 'C', true);
        $pdf->Cell(40, 8, $this->pdfText('Prix unitaire'), 1, 0, 'R', true);
        $pdf->Cell(40, 8, $this->pdfText('Total'), 1, 1, 'R', true);

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(55, 65, 81);
        foreach ($lines as $line) {
            $pdf->Cell(90, 8, $this->pdfText((string) $line['description']), 1, 0, 'L');
            $pdf->Cell(20, 8, (string) $line['quantity'], 1, 0, 'C');
            $pdf->Cell(40, 8, $this->formatMoney((int) $line['unit_cents'], 'EUR'), 1, 0, 'R');
            $pdf->Cell(40, 8, $this->formatMoney((int) $line['total_cents'], 'EUR'), 1, 1, 'R');
        }

        $pdf->Ln(5);

        $pdf->SetX(110);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(50, 7, $this->pdfText('Sous-total HT'), 0, 0, 'L');
        $pdf->Cell(40, 7, $this->formatMoney($subTotalCents, 'EUR'), 0, 1, 'R');

        $pdf->SetX(110);
        $pdf->Cell(50, 7, $this->pdfText('TVA (' . $vatRate . '%)'), 0, 0, 'L');
        $pdf->Cell(40, 7, $this->formatMoney($vatAmountCents, 'EUR'), 0, 1, 'R');

        $pdf->SetX(110);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(50, 8, $this->pdfText('Total TTC'), 0, 0, 'L');
        $pdf->Cell(40, 8, $this->formatMoney($grandTotalCents, 'EUR'), 0, 1, 'R');

        $pdf->SetAutoPageBreak(false);
        $pdf->SetY(-20);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(120, 126, 134);
        $pdf->Cell(0, 6, $this->pdfText('Merci pour votre confiance - Page ' . $pdf->PageNo() . '/{nb}'), 0, 0, 'C');

        return $pdf->Output('S');
    }

    private function resolveLogoPath(): ?string
    {
        $candidates = [
            APP_DIR . '/public/img/logo.png',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function extractInvoiceLines(object $request, object $campaign, int $fallbackTotalCents): array
    {
        $items = [];
        $pricingBreakdown = $request->pricing_breakdown ?? null;

        if (is_array($pricingBreakdown)) {
            foreach ($pricingBreakdown as $row) {
                if (!is_array($row) && !is_object($row)) {
                    continue;
                }

                $description = is_array($row) ? ($row['name'] ?? 'Ligne') : ($row->name ?? 'Ligne');
                $amount = is_array($row) ? (int) ($row['amount'] ?? 0) : (int) ($row->amount ?? 0);

                $items[] = [
                    'description' => (string) $description,
                    'quantity' => 1,
                    'unit_cents' => $amount,
                    'total_cents' => $amount,
                ];
            }
        }

        $rawData = $request->data ?? null;

        if (empty($items) && is_object($rawData) && isset($rawData->pay) && is_object($rawData->pay) && isset($rawData->pay->pricing_breakdown) && is_array($rawData->pay->pricing_breakdown)) {
            foreach ($rawData->pay->pricing_breakdown as $row) {
                if (!is_array($row) && !is_object($row)) {
                    continue;
                }

                $description = is_array($row) ? ($row['name'] ?? 'Ligne') : ($row->name ?? 'Ligne');
                $amount = is_array($row) ? (int) ($row['amount'] ?? 0) : (int) ($row->amount ?? 0);

                $items[] = [
                    'description' => (string) $description,
                    'quantity' => 1,
                    'unit_cents' => $amount,
                    'total_cents' => $amount,
                ];
            }
        }

        if (empty($items)) {
            $campaignTitle = (string) ($campaign->title ?? 'Campagne');
            $items[] = [
                'description' => 'Cotisation - ' . $campaignTitle,
                'quantity' => 1,
                'unit_cents' => $fallbackTotalCents,
                'total_cents' => $fallbackTotalCents,
            ];
        }

        return $items;
    }

    private function formatMoney(int $cents, string $currency): string
    {
        return number_format($cents / 100, 2, ',', ' ') . ' ' . $currency;
    }

    private function pdfText(string $text): string
    {
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
        return $converted === false ? $text : $converted;
    }
}
