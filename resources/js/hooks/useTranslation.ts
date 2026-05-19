import { usePage } from '@inertiajs/react';
import en from '@/locales/en.json';
import km from '@/locales/km.json';
import zh from '@/locales/zh.json';

type TranslationKey = keyof typeof en;

const translations: Record<string, Record<string, string>> = { en, km, zh };

export function useTranslation() {
    const { locale } = usePage<{ locale: string }>().props;
    const lang = locale && translations[locale] ? locale : 'km';

    function t(key: TranslationKey, replacements?: Record<string, string>): string {
        let value = translations[lang]?.[key] ?? translations.en[key] ?? key;

        if (replacements) {
            Object.entries(replacements).forEach(([k, v]) => {
                value = value.replace(`:${k}`, v);
            });
        }

        return value;
    }

    /** Get the localized value from an i18n JSON column (e.g. name_i18n) */
    function localized(i18nObj: Record<string, string> | undefined | null): string {
        if (!i18nObj) return '';
        return i18nObj[lang] ?? i18nObj.en ?? Object.values(i18nObj)[0] ?? '';
    }

    return { t, localized, locale: lang };
}
