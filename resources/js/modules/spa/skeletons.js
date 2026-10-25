// ==========================================================================
// Pixel-Aligned Skeleton Loading Layouts (1:1 Native App Shell Mirroring)
// ==========================================================================

const box = (w, h, r = '6px', extraStyle = '') => 
    `<span class="skeleton-box" style="width:${w};height:${h};border-radius:${r};${extraStyle}"></span>`;

const skeletonHeading = (titleW = '260px', subtitleW = '380px', actions = [140, 110]) => `
    <div class="page-heading">
        <div>
            <h1>${box(titleW, '32px', '6px')}</h1>
            <p class="lede" style="margin-top: 6px;">${box(subtitleW, '15px', '4px')}</p>
        </div>
        <div class="page-heading-actions">
            ${actions.map(w => box(`${w}px`, '38px', '8px')).join('')}
        </div>
    </div>
`;

export const getSkeletonDashboardHtml = () => `
<div class="skeleton-wrapper">
    ${skeletonHeading('320px', '480px', [130, 100])}

    {{-- 4 Metric Cards --}}
    <section class="metric-grid" style="margin-bottom: 28px;">
        ${[1, 2, 3, 4].map(() => `
            <div class="metric">
                <div class="metric-head">
                    ${box('110px', '14px', '4px')}
                    ${box('28px', '28px', '8px')}
                </div>
                <div style="margin: 10px 0 6px;">${box('64px', '34px', '6px')}</div>
                <div>${box('140px', '12px', '4px')}</div>
            </div>
        `).join('')}
    </section>

    {{-- Agenda Kalender Asesmen --}}
    <section class="panel" style="padding: 22px 24px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                ${box('220px', '20px', '4px')}
                ${box('50px', '22px', '999px')}
            </div>
            ${box('110px', '32px', '8px')}
        </div>
        <div style="display: grid; gap: 10px;">
            ${[1, 2, 3].map(() => `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: var(--surface-subtle); border: 1px solid var(--line); border-radius: 10px;">
                    <div style="display: flex; gap: 14px; align-items: center;">
                        ${box('44px', '44px', '10px')}
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            ${box('240px', '15px', '4px')}
                            ${box('160px', '12px', '4px')}
                        </div>
                    </div>
                    ${box('80px', '24px', '999px')}
                </div>
            `).join('')}
        </div>
    </section>

    {{-- Status Pengawasan Surveilen KAN --}}
    <section class="panel" style="padding: 22px 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            ${box('250px', '20px', '4px')}
            ${box('120px', '32px', '8px')}
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px;">
            ${[1, 2, 3].map(() => `
                <div style="border: 1px solid var(--line); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        ${box('80px', '14px', '4px')}
                        ${box('40px', '20px', '999px')}
                    </div>
                    ${box('100%', '42px', '8px')}
                </div>
            `).join('')}
        </div>
    </section>
</div>
`;

export const getSkeletonTableHtml = () => `
<div class="skeleton-wrapper">
    ${skeletonHeading('240px', '440px', [160, 110, 120])}

    <section class="panel table-panel-borderless">
        <div class="table-filters" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--line);">
            <div class="table-filter-grid">
                ${[1, 2, 3, 4].map(() => `
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        ${box('90px', '12px', '4px')}
                        ${box('100%', '42px', '8px')}
                    </div>
                `).join('')}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 44px;">${box('18px', '18px', '4px')}</th>
                        <th style="width: 140px;">${box('110px', '13px', '4px')}</th>
                        <th>${box('180px', '13px', '4px')}</th>
                        <th style="width: 140px;">${box('100px', '13px', '4px')}</th>
                        <th style="width: 250px;">${box('120px', '13px', '4px')}</th>
                        <th style="width: 60px;">${box('40px', '13px', '4px')}</th>
                    </tr>
                </thead>
                <tbody>
                    ${[1, 2, 3, 4, 5, 6, 7].map(() => `
                        <tr>
                            <td>${box('18px', '18px', '4px')}</td>
                            <td>${box('100px', '16px', '4px')}</td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    ${box('220px', '15px', '4px')}
                                    ${box('140px', '12px', '4px')}
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    ${box('90px', '13px', '4px')}
                                    ${box('75px', '18px', '999px')}
                                </div>
                            </td>
                            <td>
                                <div style="background: var(--surface-subtle); border: 1px solid var(--line); border-radius: 8px; padding: 6px 9px; display: flex; flex-direction: column; gap: 6px;">
                                    ${box('85px', '16px', '999px')}
                                    ${box('160px', '12px', '4px')}
                                </div>
                            </td>
                            <td>${box('40px', '14px', '4px')}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    </section>
</div>
`;

export const getSkeletonCalendarHtml = () => `
<div class="skeleton-wrapper">
    ${skeletonHeading('200px', '420px', [])}

    <div class="gcal-shell">
        <aside class="gcal-sidebar">
            <div style="padding: 16px; border-bottom: 1px solid var(--line);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    ${box('100px', '16px', '4px')}
                    <div style="display: flex; gap: 4px;">
                        ${box('24px', '24px', '4px')}
                        ${box('24px', '24px', '4px')}
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center;">
                    ${Array.from({ length: 35 }).map(() => box('100%', '20px', '4px')).join('')}
                </div>
            </div>
            <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                ${box('100px', '13px', '4px')}
                ${[1, 2, 3, 4].map(() => `
                    <div style="display: flex; align-items: center; gap: 10px;">
                        ${box('16px', '16px', '4px')}
                        ${box('120px', '14px', '4px')}
                    </div>
                `).join('')}
            </div>
        </aside>

        <main class="gcal-main">
            <div class="gcal-toolbar">
                <div style="display: flex; align-items: center; gap: 12px;">
                    ${box('80px', '34px', '8px')}
                    <div style="display: flex; gap: 4px;">
                        ${box('32px', '32px', '6px')}
                        ${box('32px', '32px', '6px')}
                    </div>
                    ${box('150px', '22px', '4px')}
                </div>
                <div style="display: flex; gap: 6px;">
                    ${box('80px', '34px', '8px')}
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(7, 1fr); border-bottom: 1px solid var(--line); padding: 8px 0; text-align: center;">
                ${['SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB', 'MIN'].map(() => `
                    <div style="display: flex; justify-content: center;">${box('28px', '12px', '3px')}</div>
                `).join('')}
            </div>

            <div style="display: grid; grid-template-columns: repeat(7, 1fr); min-height: 480px;">
                ${Array.from({ length: 35 }).map(() => `
                    <div style="border-right: 1px solid var(--line); border-bottom: 1px solid var(--line); min-height: 85px; padding: 8px; display: flex; flex-direction: column; gap: 6px;">
                        <div style="display: flex; justify-content: flex-end;">${box('18px', '12px', '3px')}</div>
                    </div>
                `).join('')}
            </div>
        </main>
    </div>
</div>
`;

export const getSkeletonDetailHtml = () => `
<div class="skeleton-wrapper">
    <div class="lpk-header-back-wrap" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            ${box('16px', '16px', '4px')}
            ${box('160px', '14px', '4px')}
        </div>
    </div>

    <div class="lpk-show-header" style="margin-bottom: 24px;">
        <div class="lpk-header-row">
            <div class="lpk-header-title-group" style="display: flex; flex-direction: column; gap: 10px;">
                ${box('340px', '30px', '6px')}
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    ${box('90px', '22px', '999px')}
                    ${box('120px', '22px', '999px')}
                    ${box('80px', '22px', '999px')}
                </div>
            </div>
            <div class="lpk-header-actions" style="display: flex; gap: 8px;">
                ${box('100px', '36px', '8px')}
                ${box('80px', '36px', '8px')}
            </div>
        </div>
    </div>

    <div class="panel" style="padding: 20px 24px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            ${box('180px', '18px', '4px')}
            ${box('100px', '20px', '999px')}
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px;">
            ${[1, 2, 3].map(() => `
                <div style="border: 1px solid var(--line); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; justify-content: space-between;">
                        ${box('90px', '16px', '4px')}
                        ${box('60px', '20px', '999px')}
                    </div>
                    ${box('100%', '14px', '4px')}
                    ${box('80%', '12px', '4px')}
                </div>
            `).join('')}
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px;">
        ${[1, 2].map(() => `
            <div class="panel" style="padding: 22px;">
                <div style="margin-bottom: 16px;">${box('140px', '18px', '4px')}</div>
                <div style="display: grid; gap: 14px;">
                    ${[1, 2, 3, 4].map(() => `
                        <div style="display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px solid var(--line);">
                            ${box('110px', '14px', '4px')}
                            ${box('180px', '14px', '4px')}
                        </div>
                    `).join('')}
                </div>
            </div>
        `).join('')}
    </div>
</div>
`;

export const getSkeletonFormHtml = () => `
<div class="skeleton-wrapper">
    <div class="lpk-header-back-wrap" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            ${box('16px', '16px', '4px')}
            ${box('160px', '14px', '4px')}
        </div>
    </div>

    <div class="lpk-form-header" style="margin-bottom: 24px;">
        <div class="lpk-header-row">
            <div class="lpk-header-title-group" style="display: flex; flex-direction: column; gap: 8px;">
                ${box('260px', '28px', '6px')}
                ${box('380px', '14px', '4px')}
            </div>
            <div class="lpk-header-actions" style="display: flex; gap: 8px;">
                ${box('80px', '36px', '8px')}
                ${box('140px', '36px', '8px')}
            </div>
        </div>
    </div>

    <div class="panel" style="padding: 24px;">
        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px;">
            ${[1, 2, 3, 4, 5, 6].map(() => `
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    ${box('120px', '13px', '4px')}
                    ${box('100%', '42px', '8px')}
                </div>
            `).join('')}
        </div>
    </div>
</div>
`;

export const renderSkeletonForType = (type) => {
    switch (type) {
        case 'dashboard':
            return getSkeletonDashboardHtml();
        case 'calendar':
            return getSkeletonCalendarHtml();
        case 'detail':
            return getSkeletonDetailHtml();
        case 'form':
            return getSkeletonFormHtml();
        case 'table':
        default:
            return getSkeletonTableHtml();
    }
};
