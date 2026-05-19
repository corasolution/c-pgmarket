import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import { formatPrice } from '@/lib/utils';
import { useTranslation } from '@/hooks/useTranslation';
import { MapPin, Star, Plus, ChevronDown, Pencil, Check } from 'lucide-react';
import type { Cart } from '@/types';

interface SavedAddress {
    id: number;
    label: string;
    name: string;
    phone: string;
    address_line: string;
    city: string;
    province: string | null;
    is_default: boolean;
}

interface Props {
    cart: Cart;
    addresses: SavedAddress[];
}

interface ShippingForm {
    shipping_address: {
        name: string;
        phone: string;
        address_line: string;
        city: string;
        province: string;
    };
    note: string;
}

export default function Checkout({ cart, addresses }: Props) {
    const { t, localized } = useTranslation();
    const items = cart.items ?? [];
    const total = items.reduce(
        (sum, item) => sum + item.unit_price_cents * item.quantity,
        0,
    );

    const defaultAddr = addresses.find((a) => a.is_default) ?? addresses[0];
    const hasSavedAddresses = addresses.length > 0;

    // If buyer has saved addresses, start with form hidden; otherwise show form
    const [showForm, setShowForm] = useState(!hasSavedAddresses);
    const [showPicker, setShowPicker] = useState(false);
    const [selectedAddrId, setSelectedAddrId] = useState<number | null>(defaultAddr?.id ?? null);

    const { data, setData, post, processing, errors } = useForm<ShippingForm>({
        shipping_address: {
            name: defaultAddr?.name ?? '',
            phone: defaultAddr?.phone ?? '',
            address_line: defaultAddr?.address_line ?? '',
            city: defaultAddr?.city ?? '',
            province: defaultAddr?.province ?? '',
        },
        note: '',
    });

    function selectAddress(addr: SavedAddress) {
        setData('shipping_address', {
            name: addr.name,
            phone: addr.phone,
            address_line: addr.address_line,
            city: addr.city,
            province: addr.province ?? '',
        });
        setSelectedAddrId(addr.id);
        setShowPicker(false);
        setShowForm(false);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('checkout.store'));
    }

    const selectedAddr = addresses.find((a) => a.id === selectedAddrId);

    const field = (label: string, key: keyof ShippingForm['shipping_address'], required = true) => (
        <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">
                {label}{required && <span className="text-red-500"> *</span>}
            </label>
            <input
                value={data.shipping_address[key]}
                onChange={(e) => setData('shipping_address', { ...data.shipping_address, [key]: e.target.value })}
                className="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
            />
            {errors[`shipping_address.${key}` as keyof typeof errors] && (
                <p className="mt-1 text-xs text-red-500">{errors[`shipping_address.${key}` as keyof typeof errors]}</p>
            )}
        </div>
    );

    return (
        <StorefrontLayout>
            <Head title={t('checkout.title')} />
            <div className="mx-auto max-w-2xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">{t('checkout.title')}</h1>

                {/* Order Summary */}
                <div className="mb-6 rounded-lg border bg-white p-4">
                    <h2 className="mb-4 text-lg font-semibold">{t('checkout.order_summary')}</h2>
                    {items.map((item) => (
                        <div key={item.id} className="flex justify-between py-2 text-sm">
                            <span>{localized(item.variant?.product?.name_i18n) ?? 'Product'} × {item.quantity}</span>
                            <span>{formatPrice(item.unit_price_cents * item.quantity)}</span>
                        </div>
                    ))}
                    <div className="mt-4 flex justify-between border-t pt-4 font-semibold">
                        <span>{t('common.total')}</span>
                        <span>{formatPrice(total)}</span>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="rounded-lg border bg-white p-4">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-lg font-semibold">{t('checkout.shipping_address')}</h2>
                            <Link
                                href={route('addresses.index')}
                                className="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                            >
                                <Plus className="h-3 w-3" /> {t('checkout.manage_addresses')}
                            </Link>
                        </div>

                        {/* Selected address card (shown when form is hidden) */}
                        {hasSavedAddresses && !showForm && selectedAddr && (
                            <div className="rounded-xl border border-green-200 bg-green-50/50 p-4">
                                <div className="flex items-start justify-between">
                                    <div className="flex items-start gap-3">
                                        <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600">
                                            <Check className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <div className="mb-0.5 flex items-center gap-2">
                                                <span className="text-sm font-semibold text-gray-900">{selectedAddr.label}</span>
                                                {selectedAddr.is_default && (
                                                    <span className="inline-flex items-center gap-0.5 rounded-full bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium text-primary">
                                                        <Star className="h-2.5 w-2.5 fill-current" /> {t('checkout.default')}
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-sm font-medium text-gray-800">{selectedAddr.name}</p>
                                            <p className="text-sm text-gray-500">{selectedAddr.phone}</p>
                                            <p className="mt-0.5 text-sm text-gray-600">
                                                {selectedAddr.address_line}, {selectedAddr.city}
                                                {selectedAddr.province ? `, ${selectedAddr.province}` : ''}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex gap-1">
                                        {addresses.length > 1 && (
                                            <button
                                                type="button"
                                                onClick={() => setShowPicker(!showPicker)}
                                                className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                                title="Change address"
                                            >
                                                <ChevronDown className={`h-4 w-4 transition-transform ${showPicker ? 'rotate-180' : ''}`} />
                                            </button>
                                        )}
                                        <button
                                            type="button"
                                            onClick={() => setShowForm(true)}
                                            className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-blue-600"
                                            title="Edit address"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                {/* Address picker dropdown */}
                                {showPicker && addresses.length > 1 && (
                                    <div className="mt-3 space-y-2 border-t border-green-200 pt-3">
                                        <p className="text-xs font-medium text-gray-500">{t('checkout.select_address')}</p>
                                        {addresses
                                            .filter((a) => a.id !== selectedAddrId)
                                            .map((addr) => (
                                                <button
                                                    key={addr.id}
                                                    type="button"
                                                    onClick={() => selectAddress(addr)}
                                                    className="w-full rounded-lg border border-gray-200 p-3 text-left text-sm transition-colors hover:border-primary hover:bg-primary/5"
                                                >
                                                    <div className="mb-0.5 flex items-center gap-1.5">
                                                        <MapPin className="h-3.5 w-3.5 text-gray-400" />
                                                        <span className="font-medium text-gray-800">{addr.label}</span>
                                                        {addr.is_default && (
                                                            <Star className="h-3 w-3 fill-primary text-primary" />
                                                        )}
                                                    </div>
                                                    <p className="text-gray-600">{addr.name} &middot; {addr.phone}</p>
                                                    <p className="truncate text-gray-400">
                                                        {addr.address_line}, {addr.city}
                                                    </p>
                                                </button>
                                            ))}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Manual form (shown when no addresses or user clicks edit) */}
                        {showForm && (
                            <div>
                                {/* Quick select if addresses exist */}
                                {hasSavedAddresses && (
                                    <div className="mb-4">
                                        <div className="mb-2 flex items-center justify-between">
                                            <label className="text-sm font-medium text-gray-600">
                                                {t('checkout.select_saved')}
                                            </label>
                                            {selectedAddr && (
                                                <button
                                                    type="button"
                                                    onClick={() => { selectAddress(selectedAddr); setShowForm(false); }}
                                                    className="text-xs font-medium text-primary hover:underline"
                                                >
                                                    {t('checkout.cancel_editing')}
                                                </button>
                                            )}
                                        </div>
                                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            {addresses.map((addr) => {
                                                const isSelected =
                                                    data.shipping_address.name === addr.name &&
                                                    data.shipping_address.phone === addr.phone &&
                                                    data.shipping_address.address_line === addr.address_line;

                                                return (
                                                    <button
                                                        key={addr.id}
                                                        type="button"
                                                        onClick={() => selectAddress(addr)}
                                                        className={`rounded-lg border p-3 text-left text-sm transition-colors ${
                                                            isSelected
                                                                ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                                                                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                                        }`}
                                                    >
                                                        <div className="mb-1 flex items-center gap-1.5">
                                                            <MapPin className="h-3.5 w-3.5 text-gray-400" />
                                                            <span className="font-medium text-gray-800">{addr.label}</span>
                                                            {addr.is_default && (
                                                                <Star className="h-3 w-3 fill-primary text-primary" />
                                                            )}
                                                        </div>
                                                        <p className="text-gray-600">{addr.name}</p>
                                                        <p className="truncate text-gray-400">
                                                            {addr.address_line}, {addr.city}
                                                        </p>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                <div className="space-y-3">
                                    {field(t('checkout.recipient_name'), 'name')}
                                    {field(t('checkout.phone'), 'phone')}
                                    {field(t('checkout.street_address'), 'address_line')}
                                    {field(t('checkout.city'), 'city')}
                                    {field(t('checkout.province'), 'province', false)}
                                </div>
                            </div>
                        )}

                        {/* No addresses prompt */}
                        {!hasSavedAddresses && !showForm && (
                            <button
                                type="button"
                                onClick={() => setShowForm(true)}
                                className="w-full rounded-lg border-2 border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 hover:border-primary hover:text-primary"
                            >
                                <MapPin className="mx-auto mb-2 h-6 w-6" />
                                {t('checkout.shipping_address')}
                            </button>
                        )}
                    </div>

                    <div className="rounded-lg border bg-white p-4">
                        <label className="mb-1 block text-sm font-medium text-gray-700">{t('checkout.order_note')}</label>
                        <textarea
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            rows={3}
                            className="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-lg bg-primary py-3 font-semibold text-white hover:bg-primary/90 disabled:opacity-50"
                    >
                        {processing ? t('checkout.placing_order') : t('checkout.place_order')}
                    </button>
                </form>
            </div>
        </StorefrontLayout>
    );
}
