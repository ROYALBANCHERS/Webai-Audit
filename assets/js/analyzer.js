/**
 * Analyzer Module - Displays audit results
 */

const Analyzer = (() => {
    let currentData = null;

    /**
     * Display audit results
     */
    function displayResults(data) {
        currentData = data;
        window.currentAuditData = data;

        // Show results section
        UI.showSection('results');

        // Update overview
        updateOverview(data);

        // Display tech stack
        displayTechStack(data.tech_stack || {});

        // Display issues
        displayIssues(data.issues || []);

        // Display SEO results
        displaySeo(data.seo || {});

        // Display competitors
        displayCompetitors(data.competitors || {});

        // Display GitHub results
        displayGitHub(data.github || {});
    }

    /**
     * Update overview section
     */
    function updateOverview(data) {
        // Scores
        const overallScore = data.overall_score || 0;
        const seoScore = data.seo?.score || 0;
        const pagesCount = data.crawler?.pages_crawled || 1;

        animateScore('overallScore', overallScore);
        animateScore('seoScore', seoScore);
        document.getElementById('pagesCount').textContent = pagesCount;
        document.getElementById('analyzedUrl').textContent = data.url || '';

        // Performance metrics
        const loadTime = data.crawler?.total_time || 0;
        document.getElementById('loadTime').textContent = UI.formatDuration(loadTime);

        // Quick stats
        const techCount = data.tech_stack?.all?.length || 0;
        const issuesCount = data.issues?.length || 0;

        document.getElementById('techCount').textContent = techCount;
        document.getElementById('issuesCount').textContent = issuesCount;

        // SSL status
        const sslCheck = data.seo?.checks?.ssl;
        const sslStatus = document.getElementById('sslStatus');
        if (sslCheck?.has_ssl) {
            sslStatus.innerHTML = '<i class="fas fa-check-circle text-green-400"></i>';
        } else {
            sslStatus.innerHTML = '<i class="fas fa-times-circle text-red-400"></i>';
        }

        // Mobile status
        const mobileCheck = data.seo?.checks?.mobile;
        const mobileStatus = document.getElementById('mobileStatus');
        if (mobileCheck?.has_viewport) {
            mobileStatus.innerHTML = '<i class="fas fa-check-circle text-green-400"></i>';
        } else {
            mobileStatus.innerHTML = '<i class="fas fa-times-circle text-red-400"></i>';
        }

        // Tech stack preview
        displayTechStackPreview(data.tech_stack || {});
    }

    /**
     * Animate score
     */
    function animateScore(elementId, targetScore) {
        const element = document.getElementById(elementId);
        const duration = 1000;
        const steps = 30;
        const increment = targetScore / steps;
        let current = 0;

        const timer = setInterval(() => {
            current += increment;
            if (current >= targetScore) {
                current = targetScore;
                clearInterval(timer);
            }
            element.textContent = Math.round(current);
        }, duration / steps);

        // Update color
        const scoreInfo = UI.formatScore(targetScore);
        element.className = `text-5xl font-bold ${scoreInfo.color}`;
    }

    /**
     * Display tech stack preview
     */
    function displayTechStackPreview(techStack) {
        const container = document.getElementById('techStackPreview');
        container.innerHTML = '';

        const allTech = techStack.all || [];
        const preview = allTech.slice(0, 10);

        preview.forEach(tech => {
            const tag = document.createElement('span');
            tag.className = 'feature-tag';
            tag.textContent = tech.name;
            container.appendChild(tag);
        });

        if (allTech.length > 10) {
            const more = document.createElement('span');
            more.className = 'text-gray-400 text-sm';
            more.textContent = `+${allTech.length - 10} more`;
            container.appendChild(more);
        }
    }

    /**
     * Display full tech stack
     */
    function displayTechStack(techStack) {
        const container = document.getElementById('techStackGrid');
        container.innerHTML = '';

        const categories = [
            { key: 'frameworks', icon: 'fa-cubes', title: 'Frameworks' },
            { key: 'libraries', icon: 'fa-book', title: 'Libraries' },
            { key: 'analytics', icon: 'fa-chart-line', title: 'Analytics' },
            { key: 'cms', icon: 'fa-cms', title: 'CMS' },
            { key: 'servers', icon: 'fa-server', title: 'Servers' },
            { key: 'cdn', icon: 'fa-cloud', title: 'CDN' },
            { key: 'fonts', icon: 'fa-font', title: 'Fonts' },
            { key: 'payments', icon: 'fa-credit-card', title: 'Payments' },
            { key: 'tracking', icon: 'fa-eye', title: 'Tracking' },
        ];

        categories.forEach(category => {
            const items = techStack[category.key] || [];
            if (items.length === 0) return;

            const card = document.createElement('div');
            card.className = 'tech-card rounded-xl p-6';

            card.innerHTML = `
                <div class="flex items-center mb-4">
                    <i class="fas ${category.icon} text-blue-400 mr-2"></i>
                    <h3 class="font-semibold">${category.title}</h3>
                    <span class="ml-auto text-gray-400 text-sm">${items.length}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    ${items.map(item => `
                        <span class="feature-tag">
                            ${item.name}
                            ${item.version ? `<small class="opacity-60">v${item.version}</small>` : ''}
                        </span>
                    `).join('')}
                </div>
            `;

            container.appendChild(card);
        });

        if (container.children.length === 0) {
            container.innerHTML = '<p class="text-gray-400 col-span-full text-center">No technologies detected</p>';
        }
    }

    /**
     * Display issues
     */
    function displayIssues(issues) {
        const container = document.getElementById('issuesList');
        container.innerHTML = '';

        // Combine SEO issues and crawler issues
        const allIssues = [];

        if (currentData?.seo?.issues) {
            currentData.seo.issues.forEach(issue => {
                allIssues.push({ ...issue, severity: issue.severity || 'high' });
            });
        }

        if (currentData?.crawler?.issues) {
            currentData.crawler.issues.forEach(issue => {
                allIssues.push({ ...issue, severity: issue.severity || 'medium' });
            });
        }

        if (allIssues.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-5xl text-green-400 mb-4"></i>
                    <p class="text-gray-400">No issues found!</p>
                </div>
            `;
            return;
        }

        allIssues.forEach(issue => {
            const card = document.createElement('div');
            card.className = `issue-card ${issue.severity} rounded-xl p-6`;
            card.dataset.severity = issue.severity;

            card.innerHTML = `
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="badge badge-${issue.severity === 'critical' ? 'danger' : issue.severity}">
                                ${issue.severity}
                            </span>
                            <span class="badge badge-info">${issue.type || 'general'}</span>
                        </div>
                        <h4 class="font-semibold text-lg">${issue.title || issue.message || 'Issue'}</h4>
                        ${issue.description ? `<p class="text-gray-400 mt-2">${UI.truncate(issue.description, 150)}</p>` : ''}
                        ${issue.location ? `<p class="text-gray-500 text-sm mt-1"><i class="fas fa-map-marker-alt mr-1"></i>${issue.location}</p>` : ''}
                    </div>
                </div>
                ${issue.recommendation ? `
                    <div class="mt-4 p-3 bg-gray-700/50 rounded-lg">
                        <p class="text-sm"><i class="fas fa-lightbulb text-yellow-400 mr-2"></i>${issue.recommendation}</p>
                    </div>
                ` : ''}
            `;

            container.appendChild(card);
        });
    }

    /**
     * Display SEO results
     */
    function displaySeo(seoData) {
        const container = document.getElementById('seoChecks');
        container.innerHTML = '';

        const checks = seoData.checks || {};

        const checkItems = [
            { key: 'title', label: 'Title Tag', icon: 'fa-heading' },
            { key: 'meta_description', label: 'Meta Description', icon: 'fa-align-left' },
            { key: 'headings', label: 'Headings Structure', icon: 'fa-list' },
            { key: 'images_alt', label: 'Image Alt Text', icon: 'fa-image' },
            { key: 'canonical', label: 'Canonical URL', icon: 'fa-link' },
            { key: 'robots', label: 'Robots.txt', icon: 'fa-robot' },
            { key: 'sitemap', label: 'Sitemap.xml', icon: 'fa-sitemap' },
            { key: 'schema', label: 'Schema Markup', icon: 'fa-code' },
            { key: 'speed', label: 'Page Speed', icon: 'fa-tachometer-alt' },
            { key: 'mobile', label: 'Mobile Friendly', icon: 'fa-mobile-alt' },
            { key: 'ssl', label: 'SSL Certificate', icon: 'fa-lock' },
        ];

        checkItems.forEach(item => {
            const check = checks[item.key];
            if (!check) return;

            const statusClass = check.status === 'pass' ? 'pass' : check.status === 'warning' ? 'warning' : 'fail';
            const statusIcon = check.status === 'pass' ? 'fa-check-circle text-green-400' :
                               check.status === 'warning' ? 'fa-exclamation-circle text-yellow-400' :
                               'fa-times-circle text-red-400';

            const div = document.createElement('div');
            div.className = 'seo-check';

            div.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas ${statusIcon} text-lg"></i>
                        <div>
                            <p class="font-medium">${item.label}</p>
                            <p class="text-sm text-gray-400">${check.message || 'Check completed'}</p>
                        </div>
                    </div>
                    <i class="fas fa-${item.icon} text-gray-500"></i>
                </div>
            `;

            container.appendChild(div);
        });
    }

    /**
     * Display competitors
     */
    function displayCompetitors(competitorData) {
        const container = document.getElementById('competitorsList');
        container.innerHTML = '';

        const competitors = competitorData.competitors || [];

        if (competitors.length === 0) {
            container.innerHTML = '<p class="text-gray-400 text-center py-8">No competitors found</p>';
            return;
        }

        competitors.forEach(competitor => {
            if (competitor.error) return;

            const card = document.createElement('div');
            card.className = 'competitor-card';

            const techStackBadges = (competitor.tech_stack?.all || [])
                .slice(0, 5)
                .map(t => `<span class="feature-tag">${t.name}</span>`)
                .join('');

            card.innerHTML = `
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h4 class="font-semibold text-lg">${competitor.name || 'Unknown'}</h4>
                        <a href="${competitor.url}" target="_blank" class="text-blue-400 text-sm hover:underline">
                            ${new URL(competitor.url).hostname}
                            <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                        </a>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-purple-400">${Math.round((competitor.similarity_score || 0) * 100)}%</div>
                        <div class="text-xs text-gray-400">Similarity</div>
                    </div>
                </div>
                ${competitor.description ? `<p class="text-gray-400 text-sm mb-3">${UI.truncate(competitor.description, 100)}</p>` : ''}
                <div class="flex flex-wrap gap-2 mb-3">
                    ${techStackBadges}
                </div>
                ${competitor.features?.length ? `
                    <div class="flex flex-wrap gap-1">
                        ${competitor.features.slice(0, 5).map(f => `<span class="badge badge-info text-xs">${f}</span>`).join('')}
                    </div>
                ` : ''}
            `;

            container.appendChild(card);
        });
    }

    /**
     * Display GitHub results
     */
    function displayGitHub(githubData) {
        const container = document.getElementById('githubResults');
        container.innerHTML = '';

        const repos = githubData.repositories || [];

        if (repos.length === 0) {
            container.innerHTML = '<p class="text-gray-400 text-center py-8 col-span-full">No similar repositories found</p>';
            return;
        }

        repos.forEach(repo => {
            const card = document.createElement('div');
            card.className = 'github-card';

            card.innerHTML = `
                <div class="flex items-start space-x-3">
                    <img src="${repo.owner?.avatar_url || ''}" alt="" class="w-10 h-10 rounded-full">
                    <div class="flex-1">
                        <h4 class="font-semibold">
                            <a href="${repo.url}" target="_blank" class="hover:text-blue-400">
                                ${repo.full_name}
                                <i class="fas fa-external-link-alt ml-1 text-xs opacity-50"></i>
                            </a>
                        </h4>
                        <p class="text-gray-400 text-sm mt-1">${UI.truncate(repo.description || 'No description', 100)}</p>
                        <div class="flex items-center space-x-4 mt-3 text-sm text-gray-400">
                            <span><i class="fas fa-star text-yellow-400 mr-1"></i>${repo.stars || 0}</span>
                            <span><i class="fas fa-code-branch mr-1"></i>${repo.forks || 0}</span>
                            <span><i class="fas fa-circle text-blue-400 mr-1" style="font-size: 8px;"></i>${repo.language || 'Unknown'}</span>
                        </div>
                        ${repo.topics?.length ? `
                            <div class="flex flex-wrap gap-1 mt-2">
                                ${repo.topics.slice(0, 3).map(t => `<span class="feature-tag text-xs">${t}</span>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;

            container.appendChild(card);
        });
    }

    return {
        displayResults,
    };
})();
