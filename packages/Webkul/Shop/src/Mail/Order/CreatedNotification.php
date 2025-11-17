<?php

namespace Webkul\Shop\Mail\Order;

use App\Services\PackingSlipGenerator;
use Carbon\Carbon;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Checkout\Models\Cart;
use Webkul\Sales\Contracts\Order;
use Webkul\Shop\Mail\Mailable;

class CreatedNotification extends Mailable
{
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Order $order, $extraParam = null) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    $this->order->customer_email,
                    $this->order->customer_full_name
                ),
            ],
            subject: trans('shop::app.emails.orders.created.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.orders.created',
        );
    }

    public function build()
    {
        \Log::info('DEBUG: Shop mail build() tetiklendi - Order ID: ' . $this->order->id);

        try {
            // PDF olustur bakim ...
            $pdf = PackingSlipGenerator::makePdf($this->order);

            // Cart’tan notu al
            $cart = Cart::find($this->order->cart_id);
            $customerNoteRaw = $cart?->customer_note;

            // Tarihi biçimlendir
            try {
                $customerNote = Carbon::parse($customerNoteRaw)->format('l d F'); // örnek: Friday 21 November
            } catch (\Exception $e) {
                $customerNote = $customerNoteRaw ?: 'NoNote';
            }

            // Bilgileri temizle
            $sanitize = function($string) {
                $string = preg_replace('/[^A-Za-z0-9çÇşŞğĞüÜöÖıİ\s]/u', '', $string);
                $string = preg_replace('/\s+/', ' ', $string);
                return trim($string);
            };

            $customerName = $sanitize($this->order->customer_full_name ?? 'Customer');
            $companyName = $sanitize($this->order->shipping_address->company_name ?? $customerName);
            $customerNote = $sanitize($customerNote);
            $orderId = $this->order->increment_id;

            $fileName = "{$companyName} - {$customerName} - Order#{$orderId} - For {$customerNote}.pdf";

            // Mail’i oluştur, PDF ekle ve BCC ekle
            return $this->view('shop::emails.orders.created')
                ->subject(trans('shop::app.emails.orders.created.subject'))
                ->to($this->order->customer_email)
                ->bcc('anatoliaturkishfoodweb@gmail.com') // <-- buraya BCC ekledik
                ->attachData(
                    $pdf->output(),
                    $fileName,
                    ['mime' => 'application/pdf']
                );

        } catch (\Exception $e) {
            \Log::error('Shop mail build() error: ' . $e->getMessage());
            return $this->view('shop::emails.orders.created');
        }
    }


}
