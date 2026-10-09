// ==========================================================================
// Lightweight Skeleton Loading Layouts per Route Type
// ==========================================================================

const box = (w, h, r = '6px') => `<span class="skeleton-box" style="width:${w};height:${h};border-radius:${r};"></span>`;

const skeletonHeading = (titleW = '260px') => `
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            ${box(titleW, '30px', '6px')}
            ${box('380px', '14px', '4px')}
        </div>
        <div style="display:flex;gap:10px;">
            ${box('110px', '36px', '8px')}
        </div>
    </div>
`;

export const getSkeletonDashboardHtml = () => `
<div class="skeleton-wrapper">
    ${skeletonHeading('280px')}
    <div class="metric-grid" style="margin-bottom:24px;">
        ${[1, 2, 3, 4].map(() => `
            <div class="skeleton-metric-card">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    ${box('100px', '13px')}
                    ${box('28px', '28px', '8px')}
                </div>
                <div style="margin-top:10px;">
                    ${box('56px', '32px', '6px')}
                </div>
            </div>
        `).join('')}
    </div>
    <div class="panel" style="padding:20px;margin-bottom:24px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:16px;">
            ${box('240px', '18px')}
            ${box('90px', '24px', '999px')}
        </div>
        <div style="display:grid;gap:10px;">
            ${[1, 2, 3].map(() => `
                <div class="skeleton-table-row">
                    ${box('220px', '14px')}
                    ${box('80px', '20px', '999px')}
                </div>
            `).join('')}
        </div>
    </div>
</div>
`;

export const getSkeletonTableHtml = () => `
<div class="skeleton-wrapper">
    ${skeletonHeading('240px')}
    <div class="panel" style="padding:20px;">
        <div class="skeleton-filter-bar">
            <div class="skeleton-filter-inputs">
                ${[1, 2, 3, 4].map(() => box('100%', '38px', '8px')).join('')}
            </div>
        </div>
        <div class="skeleton-table-head">
            ${box('160px', '14px')}
            ${box('120px', '14px')}
            ${box('100px', '14px')}
            ${box('60px', '14px')}
        </div>
        ${[1, 2, 3, 4, 5, 6].map(() => `
            <div class="skeleton-table-row">
                <div style="display:flex;gap:12px;align-items:center;">
                    ${box('16px', '16px', '4px')}
                    ${box('180px', '15px')}
                </div>
                ${box('120px', '14px')}
                ${box('80px', '22px', '999px')}
                ${box('50px', '28px', '6px')}
            </div>
        `).join('')}
    </div>
</div>
`;

export const getSkeletonCalendarHtml = () => `
<div class="skeleton-wrapper">
    ${skeletonHeading('200px')}
    <div class="panel" style="padding:16px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:16px;">
            <div style="display:flex;gap:8px;">
                ${box('90px', '32px', '8px')}
                ${box('70px', '32px', '8px')}
            </div>
            ${box('140px', '32px', '8px')}
        </div>
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;min-height:360px;">
            ${Array.from({ length: 35 }).map(() => `
                <div style="background:var(--surface-subtle);border:1px solid var(--line);border-radius:6px;min-height:60px;padding:6px;">
                    ${box('20px', '12px')}
                </div>
            `).join('')}
        </div>
    </div>
</div>
`;

export const getSkeletonDetailHtml = () => `
<div class="skeleton-wrapper">
    ${skeletonHeading('320px')}
    <div class="panel" style="padding:24px;">
        <div style="display:grid;gap:16px;">
            ${[1, 2, 3, 4, 5].map(() => `
                <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--line);">
                    ${box('140px', '14px')}
                    ${box('260px', '14px')}
                </div>
            `).join('')}
        </div>
    </div>
</div>
`;

export const getSkeletonFormHtml = () => `
<div class="skeleton-wrapper">
    ${skeletonHeading('220px')}
    <div class="panel" style="padding:24px;">
        <div style="display:grid;gap:18px;">
            ${[1, 2, 3, 4].map(() => `
                <div style="display:grid;gap:6px;">
                    ${box('120px', '13px')}
                    ${box('100%', '40px', '8px')}
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
