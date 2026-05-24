<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShopVerification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

final class PrivateFileController extends Controller
{
    /**
     * Issue a short-lived signed URL for a buyer's order invoice.
     * Only the order's buyer or an admin may download it.
     */
    public function invoice(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin' && $order->buyer_id !== $user->id) {
            abort(403);
        }

        $path = "invoices/order-{$order->id}.pdf";

        // Generate a temporary signed URL (15 minutes)
        $url = URL::temporarySignedRoute(
            'files.invoice.download',
            now()->addMinutes(15),
            ['order' => $order->id],
        );

        return redirect($url);
    }

    /**
     * Serve the invoice file if the signed URL is valid.
     * Called by the temporary signed route — signature already verified by middleware.
     */
    public function downloadInvoice(Request $request, Order $order): mixed
    {
        $path = "invoices/order-{$order->id}.pdf";

        // Generate PDF on-demand if not cached
        if (! Storage::disk('local')->exists($path)) {
            $order->load('buyer', 'subOrders.shop', 'subOrders.items');

            $pdf = Pdf::loadView('pdf.invoice', ['order' => $order]);

            Storage::disk('local')->put($path, $pdf->output());
        }

        return Storage::disk('local')->download($path, "invoice-{$order->reference}.pdf");
    }

    /**
     * Issue a short-lived signed URL for a KYC document.
     * Admin-only access.
     */
    public function kycDocument(Request $request, ShopVerification $verification, string $field): RedirectResponse
    {
        if ($request->user()->role !== 'admin') {
            abort(403);
        }

        $allowed = ['business_license', 'owner_id_front', 'owner_id_back', 'bank_statement'];

        if (! in_array($field, $allowed, true)) {
            abort(404);
        }

        $url = URL::temporarySignedRoute(
            'files.kyc.download',
            now()->addMinutes(10),
            ['verification' => $verification->id, 'field' => $field],
        );

        return redirect($url);
    }

    /**
     * Serve the KYC document if the signed URL is valid.
     */
    public function downloadKyc(Request $request, ShopVerification $verification, string $field): mixed
    {
        $path = (string) ($verification->$field ?? '');

        if (! $path || ! Storage::disk('r2')->exists($path)) {
            abort(404, 'Document not found.');
        }

        return Storage::disk('r2')->download($path);
    }
}
