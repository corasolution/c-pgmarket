import { Head, Link } from '@inertiajs/react';
import { Package } from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import { useTranslation } from '@/hooks/useTranslation';

interface Shop {
    id: number;
    name: string;
    slug: string;
    logo: string | null;
    banner: string | null;
    description_i18n: { en?: string } | null;
    products_count: number;
}

interface Props {
    shops: Shop[];
}

export default function ShopsIndex({ shops }: Props) {
    const { t, localized } = useTranslation();
    return (
        <StorefrontLayout>
            <Head title="All Shops — Corasoft" />
            <div className="max-w-7xl mx-auto px-4 py-10">
                <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('shop.all_shops')}</h1>
                <p className="text-gray-500 mb-8">{t('shop.browse_vendors')}</p>

                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-5">
                    {shops.map((shop) => (
                        <Link
                            key={shop.id}
                            href={route('shops.show', shop.slug)}
                            className="group bg-white rounded-2xl border overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all"
                        >
                            {/* Banner */}
                            <div className="h-28 bg-linear-to-r from-secondary/80 to-primary/80 overflow-hidden relative">
                                {shop.banner && <img src={shop.banner} alt="" className="w-full h-full object-cover" />}
                            </div>

                            <div className="p-4 -mt-8 relative">
                                <div className="w-14 h-14 rounded-xl border-2 border-white shadow bg-white overflow-hidden mb-3">
                                    {shop.logo ? (
                                        <img src={shop.logo} alt={shop.name} className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="w-full h-full bg-linear-to-br from-primary to-secondary flex items-center justify-center text-xl font-bold text-white">
                                            {shop.name[0]}
                                        </div>
                                    )}
                                </div>
                                <h2 className="font-bold text-gray-900 group-hover:text-primary transition">{shop.name}</h2>
                                {localized(shop.description_i18n) && (
                                    <p className="text-sm text-gray-500 mt-1 line-clamp-2">{localized(shop.description_i18n)}</p>
                                )}
                                <div className="flex items-center gap-1 mt-3 text-xs text-gray-400">
                                    <Package className="w-3.5 h-3.5" />
                                    <span>{shop.products_count} {t('shop.products_count_label')}</span>
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </StorefrontLayout>
    );
}


