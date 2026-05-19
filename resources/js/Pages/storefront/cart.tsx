import { Head, Link, useForm } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import { thumbUrl, imgFallback } from '@/lib/image';
import { useTranslation } from '@/hooks/useTranslation';
import type { Cart } from '@/types';

interface Props {
    cart: Cart;
}

function formatPrice(cents: number, currency = 'USD'): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(cents / 100);
}

function CartItemRow({ item, onRemove }: { item: NonNullable<Cart['items']>[number]; onRemove: () => void }) {
    const { t, localized } = useTranslation();
    const product = item.variant?.product;
    const name = localized(product?.name_i18n) ?? item.variant?.sku ?? 'Product';
    const image = item.variant?.image ?? product?.images?.[0];

    return (
        <div className="flex items-center gap-4 rounded-lg border bg-white p-4">
            <div className="h-16 w-16 shrink-0 overflow-hidden rounded-md bg-gray-100">
                {image ? (
                    <img src={thumbUrl(image)} onError={imgFallback(image)} alt={name} className="h-full w-full object-cover" />
                ) : (
                    <div className="flex h-full items-center justify-center text-xs text-gray-400">{t('product.no_img')}</div>
                )}
            </div>

            <div className="flex-1">
                <p className="font-medium text-gray-900">{name}</p>
                {item.variant?.options && (
                    <p className="text-xs text-gray-500">{Object.values(item.variant.options).join(' / ')}</p>
                )}
                <p className="mt-1 text-sm text-gray-600">
                    {formatPrice(item.unit_price_cents, item.unit_price_currency)} × {item.quantity}
                </p>
            </div>

            <div className="text-right">
                <p className="font-semibold text-gray-900">
                    {formatPrice(item.unit_price_cents * item.quantity, item.unit_price_currency)}
                </p>
                <button
                    onClick={onRemove}
                    className="mt-1 text-xs text-red-500 hover:text-red-700"
                >
                    {t('cart.remove')}
                </button>
            </div>
        </div>
    );
}

export default function CartPage({ cart }: Props) {
    const { t } = useTranslation();
    const items = cart.items ?? [];
    const total = items.reduce((sum, item) => sum + item.unit_price_cents * item.quantity, 0);
    const currency = items[0]?.unit_price_currency ?? 'USD';

    const { delete: destroy } = useForm({});

    const handleRemove = (itemId: number) => {
        destroy(route('cart.destroy', itemId));
    };

    return (
        <StorefrontLayout>
            <Head title="Cart — Corasoft" />

            <div className="mx-auto max-w-4xl px-4 py-10">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">{t('cart.your_cart')}</h1>

                {items.length === 0 ? (
                    <div className="rounded-lg border bg-white p-12 text-center">
                        <p className="text-gray-500">{t('cart.empty')}</p>
                        <Link href={route('home')} className="mt-4 inline-block text-sm text-primary hover:underline">
                            {t('common.continue_shopping')}
                        </Link>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="space-y-3 lg:col-span-2">
                            {items.map((item) => (
                                <CartItemRow
                                    key={item.id}
                                    item={item}
                                    onRemove={() => handleRemove(item.id)}
                                />
                            ))}
                        </div>

                        <div className="h-fit rounded-lg border bg-white p-6">
                            <h2 className="mb-4 text-lg font-semibold text-gray-900">{t('cart.order_summary')}</h2>
                            <div className="flex items-center justify-between border-t pt-4">
                                <span className="font-semibold text-gray-900">{t('cart.total')}</span>
                                <span className="text-xl font-bold text-primary">
                                    {formatPrice(total, currency)}
                                </span>
                            </div>
                            <Link
                                href={route('checkout.index')}
                                className="mt-4 block w-full rounded-lg bg-primary px-4 py-3 text-center text-sm font-semibold text-white hover:bg-primary/90"
                            >
                                {t('cart.checkout')}
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}
