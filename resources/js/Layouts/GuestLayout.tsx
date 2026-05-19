import LanguageSwitcher from '@/Components/LanguageSwitcher';
import { useTranslation } from '@/hooks/useTranslation';
import { Link, usePage } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    const { siteLogo } = usePage<{ siteLogo: string }>().props;
    const { t } = useTranslation();

    const features = [
        t('guest.secure_payments'),
        t('guest.fast_delivery'),
        t('guest.trusted_vendors'),
    ];

    return (
        <div className="flex min-h-screen">
            {/* Left panel — branding */}
            <div className="hidden w-1/2 bg-linear-to-br from-indigo-600 via-purple-600 to-pink-500 lg:flex lg:flex-col lg:items-center lg:justify-center">
                <div className="relative flex flex-col items-center px-12 text-center">
                    {/* Decorative circles */}
                    <div className="absolute -top-20 -left-20 h-40 w-40 rounded-full bg-white/5" />
                    <div className="absolute -bottom-16 -right-16 h-32 w-32 rounded-full bg-white/5" />

                    <div className="relative mb-8 flex h-28 w-28 items-center justify-center rounded-3xl bg-white/15 shadow-2xl ring-1 ring-white/20 backdrop-blur-sm">
                        <img
                            src={siteLogo}
                            alt="PG Market"
                            className="h-20 w-20 object-contain drop-shadow-lg"
                            onError={(e) => {
                                (e.target as HTMLImageElement).style.display = 'none';
                                e.currentTarget.parentElement?.querySelector('.fallback-icon')?.classList.remove('hidden');
                            }}
                        />
                        <ShoppingBag className="fallback-icon hidden h-14 w-14 text-white" />
                    </div>
                    <h1 className="mb-3 text-4xl font-extrabold tracking-tight text-white">PG Market</h1>
                    <p className="max-w-xs text-lg leading-relaxed text-white/75">
                        {t('guest.tagline')}
                    </p>

                    {/* Feature pills */}
                    <div className="mt-10 flex flex-wrap justify-center gap-3">
                        {features.map((feature) => (
                            <span
                                key={feature}
                                className="rounded-full bg-white/10 px-4 py-1.5 text-sm font-medium text-white/90 ring-1 ring-white/20"
                            >
                                {feature}
                            </span>
                        ))}
                    </div>
                </div>
            </div>

            {/* Right panel — form */}
            <div className="relative flex w-full flex-col items-center justify-center bg-gray-50 px-6 py-12 lg:w-1/2">
                {/* Language switcher — top right */}
                <div className="absolute right-4 top-4">
                    <LanguageSwitcher variant="guest" />
                </div>

                {/* Mobile logo */}
                <div className="mb-8 lg:hidden">
                    <Link href="/" className="flex flex-col items-center gap-3">
                        <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-linear-to-br from-indigo-600 to-purple-600 shadow-lg">
                            <img
                                src={siteLogo}
                                alt="PG Market"
                                className="h-14 w-14 object-contain"
                                onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; }}
                            />
                        </div>
                        <span className="text-2xl font-extrabold tracking-tight text-gray-900">PG Market</span>
                    </Link>
                </div>

                <div className="w-full max-w-md">
                    <div className="rounded-2xl bg-white px-8 py-10 shadow-xl ring-1 ring-gray-100">
                        {children}
                    </div>

                    <p className="mt-6 text-center text-xs text-gray-400">
                        &copy; {new Date().getFullYear()} PG Market. {t('guest.copyright')}
                    </p>
                </div>
            </div>
        </div>
    );
}
