<?php

namespace Webkul\Admin\Mail\Order;

use App\Services\PackingSlipGenerator;
use Carbon\Carbon;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Webkul\Admin\Mail\Mailable;
use Webkul\Checkout\Models\Cart;
use Webkul\Sales\Contracts\Order;

class CreatedNotification extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    core()->getAdminEmailDetails()['email'],
                    core()->getAdminEmailDetails()['name']
                ),
            ],
            subject: trans('admin::app.emails.orders.created.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'admin::emails.orders.created',
        );
    }

    public function build()
    {
        \Log::info('DEBUG: build() tetiklendi - Order ID: ' . $this->order->id);

        try {
            // PDF oluştur
            $pdf = PackingSlipGenerator::makePdf($this->order);

            // İlgili cart kaydını al (customer_note)
            $cart = Cart::find($this->order->cart_id);
            $customerNoteRaw = $cart?->customer_note;

            // Eğer tarih formatındaysa Carbon ile biçimlendir
            try {
                $customerNote = Carbon::parse($customerNoteRaw)->format('l d F'); // örnek: Friday 21 November
            } catch (\Exception $e) {
                $customerNote = $customerNoteRaw ?: 'NoNote';
            }

            // Müşteri ve firma bilgileri
            $customerName = $this->order->customer_full_name ?? $this->order->shipping_address->name;
            $companyName = $this->order->shipping_address->company_name ?? $customerName;
            $orderId = $this->order->increment_id;

            // Temizleme fonksiyonu
            $sanitize = function($string) {
                $string = preg_replace('/[^A-Za-z0-9çÇşŞğĞüÜöÖıİ\s]/u', '', $string);
                $string = preg_replace('/\s+/', ' ', $string);
                return trim($string);
            };

            $companyName = $sanitize($companyName);
            $customerName = $sanitize($customerName);
            $customerNote = $sanitize($customerNote);

            // 🔹 PDF dosya adı
            $fileName = "{$companyName} - {$customerName} - Order#{$orderId} - For {$customerNote}.pdf";

            // Mail’i oluştur ve PDF’i ekle
            return $this->view('admin::emails.orders.created')
                ->subject(trans('admin::app.emails.orders.created.subject'))
                ->to(core()->getAdminEmailDetails()['email'])
                ->attachData(
                    $pdf->output(),
                    $fileName,
                    ['mime' => 'application/pdf']
                );


        } catch (\Exception $e) {
            \Log::error('Packing slip build() error: ' . $e->getMessage());
            return $this->view('admin::emails.orders.created');
        }
    }
}
