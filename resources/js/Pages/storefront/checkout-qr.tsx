import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import { Clock, Loader2, XCircle, CheckCircle } from 'lucide-react';
import type { Order } from '@/types';

interface Props {
    order: Order;
    orderReference: string;
    paymentData: Record<string, string>;
}

export default function CheckoutQr({ order, orderReference, paymentData }: Props) {
    const { action, ...fields } = paymentData;
    const triggeredRef = useRef(false);
    const [status, setStatus] = useState<'pending' | 'paid' | 'failed' | 'expired'>('pending');
    const [timeLeft, setTimeLeft] = useState(15 * 60); // 15-minute session

    // Auto-open PayWay popup on mount
    useEffect(() => {
        if (triggeredRef.current) return;
        triggeredRef.current = true;

        const t = setTimeout(() => {
            const btn = document.getElementById('checkout_button');
            if (btn) btn.click();
        }, 250);

        return () => clearTimeout(t);
    }, []);

    // Poll for payment status every 10s (after initial 3s delay)
    useEffect(() => {
        if (status === 'paid' || status === 'expired') return;

        let interval: ReturnType<typeof setInterval>;

        const startTimer = setTimeout(() => {
            const poll = async () => {
                try {
                    const res = await fetch(
                        route('checkout.poll', { transaction: orderReference }),
                        { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    );
                    const data = (await res.json()) as {
                        paid: boolean;
                        status: string;
                        expired: boolean;
                    };

                    if (data.paid) {
                        setStatus('paid');
                        clearInterval(interval);
                        setTimeout(() => {
                            router.visit(route('orders.index'), { replace: true });
                        }, 2000);
                    } else if (data.expired) {
                        setStatus('expired');
                        clearInterval(interval);
                        setTimeLeft(0);
                    }
                } catch {
                    // network error — keep polling
                }
            };

            poll();
            interval = setInterval(poll, 10000);
        }, 3000);

        return () => {
            clearTimeout(startTimer);
            clearInterval(interval);
        };
    }, [orderReference, status]);

    // Countdown timer
    useEffect(() => {
        if (status === 'paid' || timeLeft <= 0) return;

        const t = setTimeout(() => {
            setTimeLeft((s) => {
                if (s <= 1) {
                    setStatus('expired');
                    return 0;
                }
                return s - 1;
            });
        }, 1000);

        return () => clearTimeout(t);
    }, [timeLeft, status]);

    const minutes = Math.floor(timeLeft / 60)
        .toString()
        .padStart(2, '0');
    const seconds = (timeLeft % 60).toString().padStart(2, '0');

    return (
        <StorefrontLayout>
            <Head title="ABA KHQR Checkout" />

            <div className="mx-auto max-w-md px-4 py-12">
                <div className="text-center">
                    {status === 'paid' && (
                        <div className="rounded-2xl border border-green-200 bg-green-50 p-8">
                            <CheckCircle className="mx-auto mb-3 h-14 w-14 text-green-600" />
                            <p className="mb-1 text-xl font-bold">Payment Confirmed!</p>
                            <p className="text-sm text-gray-500">
                                Your order is now being processed. Redirecting...
                            </p>
                        </div>
                    )}

                    {status === 'expired' && (
                        <div className="rounded-2xl border border-red-200 bg-red-50 p-8">
                            <XCircle className="mx-auto mb-3 h-12 w-12 text-red-400" />
                            <p className="mb-1 text-lg font-bold">Session Expired</p>
                            <p className="mb-4 text-sm text-gray-500">
                                If you completed payment, it will be confirmed shortly.
                                Otherwise, please try again.
                            </p>
                            <a
                                href={route('cart.index')}
                                className="inline-block rounded-lg bg-red-600 px-6 py-2 text-white hover:bg-red-700"
                            >
                                Return to Cart
                            </a>
                        </div>
                    )}

                    {status === 'pending' && (
                        <>
                            <h1 className="mb-2 text-2xl font-bold">
                                Opening ABA KHQR...
                            </h1>
                            <p className="mb-1 text-sm">
                                Order:{' '}
                                <span className="font-mono font-semibold">
                                    {orderReference}
                                </span>
                            </p>
                            <p className="mb-6 text-xs text-gray-400">
                                Scan the QR code in the popup with any banking app.
                            </p>

                            <div className="mb-6 flex items-center justify-center gap-2 text-sm">
                                <Loader2 className="h-4 w-4 animate-spin" />
                                Waiting for payment confirmation...
                            </div>

                            <div className="mb-6 flex items-center justify-center gap-2 text-sm text-gray-400">
                                <Clock className="h-4 w-4" />
                                <span>
                                    Session expires in{' '}
                                    <span className="font-mono font-bold text-gray-700">
                                        {minutes}:{seconds}
                                    </span>
                                </span>
                            </div>

                            {/* Fallback button if auto-click doesn't work */}
                            <button
                                id="checkout_button"
                                type="button"
                                className="rounded-xl bg-red-600 px-8 py-3 font-bold text-white hover:bg-red-700"
                            >
                                Open ABA KHQR Payment
                            </button>
                        </>
                    )}
                </div>
            </div>

            {/* Hidden form — PayWay JS reads these fields */}
            <form
                id="aba_merchant_request"
                method="POST"
                action={action}
                target="aba_webservice"
                className="hidden"
            >
                {Object.entries(fields).map(([name, value]) => (
                    <input
                        key={name}
                        type="hidden"
                        name={name}
                        value={value ?? ''}
                    />
                ))}
            </form>
        </StorefrontLayout>
    );
}
