import { router, usePage } from '@inertiajs/react';
import { Globe } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface Language {
    code: string;
    label: string;
    flag: string;
}

const LANGUAGES: Language[] = [
    { code: 'en', label: 'English', flag: '🇺🇸' },
    { code: 'km', label: 'ខ្មែរ', flag: '🇰🇭' },
    { code: 'zh', label: '中文', flag: '🇨🇳' },
];

interface Props {
    variant?: 'default' | 'compact' | 'guest';
}

export default function LanguageSwitcher({ variant = 'default' }: Props) {
    const { locale } = usePage<{ locale: string }>().props;
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    const current = LANGUAGES.find((l) => l.code === locale) ?? LANGUAGES[0];

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    function switchLocale(code: string) {
        setOpen(false);
        if (code === locale) return;
        router.post(route('locale.switch', code), {}, { preserveState: true, preserveScroll: true });
    }

    if (variant === 'guest') {
        return (
            <div ref={ref} className="relative">
                <button
                    onClick={() => setOpen(!open)}
                    className="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
                >
                    <Globe className="h-4 w-4" />
                    <span>{current.flag} {current.code.toUpperCase()}</span>
                </button>

                {open && (
                    <div className="absolute right-0 top-full z-50 mt-1 w-40 overflow-hidden rounded-xl border border-gray-100 bg-white py-1 shadow-lg">
                        {LANGUAGES.map((lang) => (
                            <button
                                key={lang.code}
                                onClick={() => switchLocale(lang.code)}
                                className={`flex w-full items-center gap-2.5 px-3 py-2 text-sm transition hover:bg-gray-50 ${
                                    lang.code === locale ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-700'
                                }`}
                            >
                                <span className="text-base">{lang.flag}</span>
                                <span>{lang.label}</span>
                            </button>
                        ))}
                    </div>
                )}
            </div>
        );
    }

    if (variant === 'compact') {
        return (
            <div ref={ref} className="relative">
                <button
                    onClick={() => setOpen(!open)}
                    className="flex items-center gap-1 rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-white/80 ring-1 ring-white/20 transition hover:bg-white/20 hover:text-white"
                >
                    <span>{current.flag}</span>
                    <span>{current.code.toUpperCase()}</span>
                </button>

                {open && (
                    <div className="absolute right-0 top-full z-50 mt-1 w-36 overflow-hidden rounded-xl border border-gray-100 bg-white py-1 shadow-lg">
                        {LANGUAGES.map((lang) => (
                            <button
                                key={lang.code}
                                onClick={() => switchLocale(lang.code)}
                                className={`flex w-full items-center gap-2 px-3 py-2 text-sm transition hover:bg-gray-50 ${
                                    lang.code === locale ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-700'
                                }`}
                            >
                                <span>{lang.flag}</span>
                                <span>{lang.label}</span>
                            </button>
                        ))}
                    </div>
                )}
            </div>
        );
    }

    // Default variant — storefront nav
    return (
        <div ref={ref} className="relative">
            <button
                onClick={() => setOpen(!open)}
                className="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
            >
                <span className="text-base">{current.flag}</span>
                <span className="hidden sm:inline">{current.label}</span>
                <span className="sm:hidden">{current.code.toUpperCase()}</span>
                <svg className={`h-3.5 w-3.5 text-gray-400 transition ${open ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && (
                <div className="absolute right-0 top-full z-50 mt-1.5 w-44 overflow-hidden rounded-xl border border-gray-100 bg-white py-1 shadow-xl">
                    {LANGUAGES.map((lang) => (
                        <button
                            key={lang.code}
                            onClick={() => switchLocale(lang.code)}
                            className={`flex w-full items-center gap-3 px-3.5 py-2.5 text-sm transition hover:bg-gray-50 ${
                                lang.code === locale
                                    ? 'bg-indigo-50 font-semibold text-indigo-700'
                                    : 'text-gray-700'
                            }`}
                        >
                            <span className="text-lg">{lang.flag}</span>
                            <span>{lang.label}</span>
                            {lang.code === locale && (
                                <svg className="ml-auto h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            )}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
