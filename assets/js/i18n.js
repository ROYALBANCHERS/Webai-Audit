/**
 * i18n Module - Multi-language support
 */

const i18n = (() => {
    const translations = {
        en: {
            analyze: 'Analyze',
            options: 'Options',
            checkCompetitors: 'Check Competitors',
            checkGithub: 'Check GitHub',
            useBrowser: 'Use Browser',
            maxDepth: 'Max Depth',
            analyzing: 'Analyzing Website...',
            initializing: 'Initializing crawler...',
            overallScore: 'Overall Score',
            seoScore: 'SEO Score',
            pagesCrawled: 'Pages Crawled',
            analysisComplete: 'Analysis Complete',
            overview: 'Overview',
            techStack: 'Tech Stack',
            issues: 'Issues',
            seo: 'SEO',
            competitors: 'Competitors',
            github: 'GitHub',
            performance: 'Performance',
            loadTime: 'Load Time',
            pageSize: 'Page Size',
            requests: 'Requests',
            quickStats: 'Quick Stats',
            technologies: 'Technologies',
            mobile: 'Mobile',
            detectedTechnologies: 'Detected Technologies',
            footerText: 'Powered by PHP & Laravel',
            exportPDF: 'Export PDF',
            exportJSON: 'Export JSON',
        },
        hi: {
            analyze: 'विश्लेषण करें',
            options: 'विकल्प',
            checkCompetitors: 'प्रतिस्पर्धी जांचें',
            checkGithub: 'GitHub जांचें',
            useBrowser: 'ब्राउज़र का उपयोग करें',
            maxDepth: 'अधिकतम गहराई',
            analyzing: 'वेबसाइट का विश्लेषण...',
            initializing: 'क्रॉलर प्रारंभ हो रहा है...',
            overallScore: 'समग्र स्कोर',
            seoScore: 'SEO स्कोर',
            pagesCrawled: 'पृष्ठ क्रॉल किए गए',
            analysisComplete: 'विश्लेषण पूर्ण',
            overview: 'सारांश',
            techStack: 'तकनीकी स्टैक',
            issues: 'समस्याएं',
            seo: 'SEO',
            competitors: 'प्रतिस्पर्धी',
            github: 'GitHub',
            performance: 'प्रदर्शन',
            loadTime: 'लोड समय',
            pageSize: 'पृष्ठ आकार',
            requests: 'अनुरोध',
            quickStats: 'त्वरित आँकड़े',
            technologies: 'तकनीकें',
            mobile: 'मोबाइल',
            detectedTechnologies: 'पता लगाई गई तकनीकें',
            footerText: 'PHP और Laravel द्वारा संचालित',
            exportPDF: 'PDF निर्यात करें',
            exportJSON: 'JSON निर्यात करें',
        },
    };

    let currentLang = 'en';

    /**
     * Set language
     */
    function setLanguage(lang) {
        if (translations[lang]) {
            currentLang = lang;
            updatePageLanguage();
            localStorage.setItem('lang', lang);
        }
    }

    /**
     * Get translation
     */
    function t(key) {
        return translations[currentLang][key] || translations.en[key] || key;
    }

    /**
     * Update page language
     */
    function updatePageLanguage() {
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            el.textContent = t(key);
        });

        // Update lang toggle button
        const langToggle = document.getElementById('langToggle');
        if (langToggle) {
            langToggle.textContent = currentLang.toUpperCase();
        }
    }

    /**
     * Initialize i18n
     */
    function init() {
        // Load saved language or detect from browser
        const saved = localStorage.getItem('lang');
        if (saved && translations[saved]) {
            currentLang = saved;
        } else {
            const browserLang = navigator.language.slice(0, 2);
            currentLang = translations[browserLang] ? browserLang : 'en';
        }

        updatePageLanguage();

        // Setup language toggle
        const langToggle = document.getElementById('langToggle');
        if (langToggle) {
            langToggle.addEventListener('click', () => {
                setLanguage(currentLang === 'en' ? 'hi' : 'en');
            });
        }
    }

    return {
        t,
        setLanguage,
        init,
    };
})();
