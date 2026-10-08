// ==========================================================================
// Custom Skeleton Loading Layouts per Menu (Synced with Latest 2026 UI)
// ==========================================================================

export const getSkeletonDashboardHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 280px; height: 32px; border-radius: 6px;"></span>
            <span class="skeleton-box" style="width: 440px; height: 16px; margin-top: 6px; border-radius: 4px;"></span>
        </div>
        <div style="display: flex; gap: 10px;">
            <span class="skeleton-box" style="width: 130px; height: 38px; border-radius: 7px;"></span>
            <span class="skeleton-box" style="width: 140px; height: 38px; border-radius: 7px;"></span>
        </div>
    </div>

    <div class="metric-grid" style="margin-bottom: 24px;">
        ${[1, 2, 3, 4].map(() => `
            <div class="skeleton-metric-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="skeleton-box" style="width: 120px; height: 13px; border-radius: 4px;"></span>
                    <span class="skeleton-box" style="width: 32px; height: 32px; border-radius: 8px;"></span>
                </div>
                <div style="margin-top: 8px;">
                    <span class="skeleton-box" style="width: 60px; height: 34px; border-radius: 6px; display: block; margin-bottom: 6px;"></span>
                    <span class="skeleton-box" style="width: 130px; height: 12px; border-radius: 4px;"></span>
                </div>
            </div>
        `).join('')}
    </div>

    <div class="panel" style="margin-bottom: 24px; padding: 18px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <span class="skeleton-box" style="width: 340px; height: 18px; border-radius: 4px; display: block; margin-bottom: 6px;"></span>
                <span class="skeleton-box" style="width: 260px; height: 13px; border-radius: 4px;"></span>
            </div>
            <span class="skeleton-box" style="width: 120px; height: 24px; border-radius: 999px;"></span>
        </div>
        <div style="display: grid; gap: 12px;">
            ${[1, 2].map(() => `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="skeleton-box" style="width: 36px; height: 24px; border-radius: 6px;"></span>
                        <div>
                            <span class="skeleton-box" style="width: 200px; height: 15px; border-radius: 4px; display: block; margin-bottom: 5px;"></span>
                            <span class="skeleton-box" style="width: 160px; height: 12px; border-radius: 4px;"></span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <span class="skeleton-box" style="width: 80px; height: 32px; border-radius: 6px;"></span>
                        <span class="skeleton-box" style="width: 90px; height: 32px; border-radius: 6px;"></span>
                    </div>
                </div>
            `).join('')}
        </div>
    </div>

    <div class="dashboard-overview-grid">
        <div class="panel" style="padding: 18px 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="skeleton-box" style="width: 160px; height: 18px; border-radius: 4px;"></span>
                    <span class="skeleton-box" style="width: 50px; height: 20px; border-radius: 999px;"></span>
                </div>
                <span class="skeleton-box" style="width: 80px; height: 14px; border-radius: 4px;"></span>
            </div>
            <div style="display: grid; gap: 10px;">
                ${[1, 2, 3, 4].map(() => `
                    <div style="padding: 12px; border: 1px solid var(--line); border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1;">
                            <div style="display: flex; gap: 8px; margin-bottom: 6px;">
                                <span class="skeleton-box" style="width: 90px; height: 14px; border-radius: 4px;"></span>
                                <span class="skeleton-box" style="width: 60px; height: 18px; border-radius: 999px;"></span>
                            </div>
                            <span class="skeleton-box" style="width: 210px; height: 16px; border-radius: 4px; display: block; margin-bottom: 6px;"></span>
                            <span class="skeleton-box" style="width: 170px; height: 12px; border-radius: 4px;"></span>
                        </div>
                        <span class="skeleton-box" style="width: 16px; height: 16px; border-radius: 4px;"></span>
                    </div>
                `).join('')}
            </div>
        </div>

        <div class="panel" style="padding: 18px 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="skeleton-box" style="width: 140px; height: 18px; border-radius: 4px;"></span>
                    <span class="skeleton-box" style="width: 60px; height: 20px; border-radius: 999px;"></span>
                </div>
                <span class="skeleton-box" style="width: 95px; height: 14px; border-radius: 4px;"></span>
            </div>
            <div style="display: grid; gap: 10px;">
                ${[1, 2, 3, 4].map(() => `
                    <div style="padding: 12px; border: 1px solid var(--line); border-radius: 8px; display: flex; align-items: center; gap: 14px;">
                        <span class="skeleton-box" style="width: 48px; height: 48px; border-radius: 8px; flex-shrink: 0;"></span>
                        <div style="flex: 1;">
                            <span class="skeleton-box" style="width: 180px; height: 15px; border-radius: 4px; display: block; margin-bottom: 6px;"></span>
                            <span class="skeleton-box" style="width: 140px; height: 12px; border-radius: 4px;"></span>
                        </div>
                        <span class="skeleton-box" style="width: 70px; height: 22px; border-radius: 999px; flex-shrink: 0;"></span>
                    </div>
                `).join('')}
            </div>
        </div>
    </div>
</div>
`;

export const getSkeletonTableHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 160px; height: 30px; border-radius: 6px;"></span>
            <span class="skeleton-box" style="width: 420px; height: 15px; margin-top: 6px; border-radius: 4px;"></span>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <span class="skeleton-box" style="width: 145px; height: 38px; border-radius: 7px;"></span>
            <span class="skeleton-box" style="width: 105px; height: 38px; border-radius: 7px;"></span>
            <span class="skeleton-box" style="width: 125px; height: 38px; border-radius: 7px;"></span>
        </div>
    </div>

    <div class="panel" style="padding: 18px 20px;">
        <div class="skeleton-filter-bar">
            <div class="skeleton-filter-inputs">
                <div>
                    <span class="skeleton-box" style="width: 60px; height: 12px; display: block; margin-bottom: 6px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 38px; border-radius: 7px;"></span>
                </div>
                <div>
                    <span class="skeleton-box" style="width: 70px; height: 12px; display: block; margin-bottom: 6px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 38px; border-radius: 7px;"></span>
                </div>
                <div>
                    <span class="skeleton-box" style="width: 100px; height: 12px; display: block; margin-bottom: 6px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 38px; border-radius: 7px;"></span>
                </div>
                <div>
                    <span class="skeleton-box" style="width: 80px; height: 12px; display: block; margin-bottom: 6px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 38px; border-radius: 7px;"></span>
                </div>
            </div>
            <div style="display: flex; gap: 8px; margin-top: 12px;">
                <span class="skeleton-box" style="width: 95px; height: 36px; border-radius: 7px;"></span>
                <span class="skeleton-box" style="width: 75px; height: 36px; border-radius: 7px;"></span>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <div class="skeleton-table-head">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <span class="skeleton-box" style="width: 16px; height: 16px; border-radius: 4px;"></span>
                    <span class="skeleton-box" style="width: 140px; height: 13px; border-radius: 3px;"></span>
                </div>
                <span class="skeleton-box" style="width: 180px; height: 13px; border-radius: 3px;"></span>
                <span class="skeleton-box" style="width: 110px; height: 13px; border-radius: 3px;"></span>
                <span class="skeleton-box" style="width: 95px; height: 13px; border-radius: 3px;"></span>
                <span class="skeleton-box" style="width: 100px; height: 13px; border-radius: 3px;"></span>
                <span class="skeleton-box" style="width: 85px; height: 13px; border-radius: 3px;"></span>
                <span class="skeleton-box" style="width: 60px; height: 13px; border-radius: 3px;"></span>
            </div>
            ${[1, 2, 3, 4, 5, 6, 7].map(() => `
                <div class="skeleton-table-row">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <span class="skeleton-box" style="width: 16px; height: 16px; border-radius: 4px;"></span>
                        <div>
                            <span class="skeleton-box" style="width: 160px; height: 14px; border-radius: 3px; display: block; margin-bottom: 4px;"></span>
                            <span class="skeleton-box" style="width: 90px; height: 11px; border-radius: 3px;"></span>
                        </div>
                    </div>
                    <div>
                        <span class="skeleton-box" style="width: 190px; height: 14px; border-radius: 3px; display: block; margin-bottom: 4px;"></span>
                        <span class="skeleton-box" style="width: 120px; height: 11px; border-radius: 3px;"></span>
                    </div>
                    <span class="skeleton-box" style="width: 90px; height: 22px; border-radius: 999px;"></span>
                    <span class="skeleton-box" style="width: 80px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 110px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 75px; height: 22px; border-radius: 999px;"></span>
                    <div style="display: flex; gap: 6px;">
                        <span class="skeleton-box" style="width: 28px; height: 28px; border-radius: 6px;"></span>
                        <span class="skeleton-box" style="width: 28px; height: 28px; border-radius: 6px;"></span>
                    </div>
                </div>
            `).join('')}
        </div>
    </div>
</div>
`;

export const getSkeletonCalendarHtml = () => `
<div class="skeleton-wrapper">
    <div class="skeleton-heading">
        <div class="skeleton-heading-content">
            <span class="skeleton-box" style="width: 240px; height: 30px; border-radius: 6px;"></span>
            <span class="skeleton-box" style="width: 380px; height: 15px; margin-top: 6px; border-radius: 4px;"></span>
        </div>
    </div>

    <div class="gcal-shell" style="opacity: 0.9;">
        <aside class="gcal-sidebar">
            <span class="skeleton-box" style="width: 100%; height: 42px; border-radius: 8px; margin-bottom: 16px;"></span>
            <div style="padding: 12px; border: 1px solid var(--line); border-radius: 8px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span class="skeleton-box" style="width: 90px; height: 14px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 40px; height: 14px; border-radius: 3px;"></span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;">
                    ${Array.from({ length: 28 }).map(() => `
                        <span class="skeleton-box" style="height: 18px; border-radius: 3px;"></span>
                    `).join('')}
                </div>
            </div>
            <div>
                <span class="skeleton-box" style="width: 110px; height: 14px; border-radius: 3px; display: block; margin-bottom: 10px;"></span>
                ${[1, 2, 3, 4].map(() => `
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span class="skeleton-box" style="width: 14px; height: 14px; border-radius: 3px;"></span>
                        <span class="skeleton-box" style="width: 110px; height: 13px; border-radius: 3px;"></span>
                    </div>
                `).join('')}
            </div>
        </aside>

        <main class="gcal-main">
            <div class="skeleton-calendar-toolbar">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="skeleton-box" style="width: 75px; height: 34px; border-radius: 6px;"></span>
                    <span class="skeleton-box" style="width: 60px; height: 34px; border-radius: 6px;"></span>
                    <span class="skeleton-box" style="width: 160px; height: 22px; border-radius: 4px;"></span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <span class="skeleton-box" style="width: 60px; height: 32px; border-radius: 6px;"></span>
                    <span class="skeleton-box" style="width: 65px; height: 32px; border-radius: 6px;"></span>
                    <span class="skeleton-box" style="width: 55px; height: 32px; border-radius: 6px;"></span>
                    <span class="skeleton-box" style="width: 65px; height: 32px; border-radius: 6px;"></span>
                </div>
            </div>

            <div class="skeleton-calendar-headings">
                ${['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'].map(() => `
                    <span class="skeleton-box" style="width: 40px; height: 12px; margin: auto; border-radius: 3px;"></span>
                `).join('')}
            </div>

            <div class="skeleton-calendar-grid">
                ${Array.from({ length: 28 }).map((_, i) => `
                    <div class="skeleton-calendar-cell">
                        <span class="skeleton-box" style="width: 22px; height: 22px; border-radius: 4px;"></span>
                        ${i % 4 === 1 ? '<span class="skeleton-box" style="width: 90%; height: 18px; border-radius: 4px; margin-top: 4px;"></span>' : ''}
                        ${i % 7 === 3 ? '<span class="skeleton-box" style="width: 80%; height: 18px; border-radius: 4px; margin-top: 2px;"></span>' : ''}
                    </div>
                `).join('')}
            </div>
        </main>
    </div>
</div>
`;

export const getSkeletonDetailHtml = () => `
<div class="skeleton-wrapper">
    <div style="margin-bottom: 12px;">
        <span class="skeleton-box" style="width: 100px; height: 18px; border-radius: 4px;"></span>
    </div>

    <div class="panel" style="padding: 20px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="skeleton-box" style="width: 280px; height: 28px; border-radius: 6px; display: block; margin-bottom: 10px;"></span>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <span class="skeleton-box" style="width: 110px; height: 22px; border-radius: 999px;"></span>
                    <span class="skeleton-box" style="width: 130px; height: 22px; border-radius: 999px;"></span>
                    <span class="skeleton-box" style="width: 90px; height: 22px; border-radius: 999px;"></span>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <span class="skeleton-box" style="width: 100px; height: 36px; border-radius: 7px;"></span>
                <span class="skeleton-box" style="width: 90px; height: 36px; border-radius: 7px;"></span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px;">
        ${[1, 2, 3].map(() => `
            <div class="panel" style="padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span class="skeleton-box" style="width: 120px; height: 14px; border-radius: 4px;"></span>
                    <span class="skeleton-box" style="width: 65px; height: 20px; border-radius: 999px;"></span>
                </div>
                <span class="skeleton-box" style="width: 150px; height: 18px; border-radius: 4px; display: block; margin-bottom: 8px;"></span>
                <span class="skeleton-box" style="width: 100px; height: 12px; border-radius: 4px;"></span>
            </div>
        `).join('')}
    </div>

    <div class="detail-grid">
        <div class="panel" style="padding: 20px;">
            <span class="skeleton-box" style="width: 140px; height: 16px; border-radius: 4px; display: block; margin-bottom: 18px;"></span>
            ${[1, 2, 3, 4, 5].map(() => `
                <div class="skeleton-detail-item">
                    <span class="skeleton-box" style="width: 90px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 180px; height: 14px; border-radius: 3px;"></span>
                </div>
            `).join('')}
        </div>

        <div class="panel" style="padding: 20px;">
            <span class="skeleton-box" style="width: 160px; height: 16px; border-radius: 4px; display: block; margin-bottom: 18px;"></span>
            <div style="display: grid; gap: 12px;">
                ${[1, 2, 3].map(() => `
                    <div style="padding: 12px; border: 1px solid var(--line); border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span class="skeleton-box" style="width: 150px; height: 15px; border-radius: 4px; display: block; margin-bottom: 4px;"></span>
                            <span class="skeleton-box" style="width: 100px; height: 12px; border-radius: 3px;"></span>
                        </div>
                        <span class="skeleton-box" style="width: 70px; height: 22px; border-radius: 999px;"></span>
                    </div>
                `).join('')}
            </div>
        </div>
    </div>
</div>
`;

export const getSkeletonFormHtml = () => `
<div class="skeleton-wrapper">
    <div style="margin-bottom: 12px;">
        <span class="skeleton-box" style="width: 110px; height: 18px; border-radius: 4px;"></span>
    </div>

    <div class="panel" style="padding: 20px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="skeleton-box" style="width: 240px; height: 26px; border-radius: 6px; display: block; margin-bottom: 6px;"></span>
                <span class="skeleton-box" style="width: 380px; height: 14px; border-radius: 4px;"></span>
            </div>
            <div style="display: flex; gap: 8px;">
                <span class="skeleton-box" style="width: 80px; height: 38px; border-radius: 7px;"></span>
                <span class="skeleton-box" style="width: 135px; height: 38px; border-radius: 7px;"></span>
            </div>
        </div>
    </div>

    <div class="detail-grid">
        <div class="panel" style="padding: 20px;">
            <span class="skeleton-box" style="width: 160px; height: 16px; border-radius: 4px; display: block; margin-bottom: 18px;"></span>
            <div style="display: grid; gap: 16px;">
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 110px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 130px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 95px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 120px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 80px; border-radius: 7px;"></span>
                </div>
            </div>
        </div>

        <div class="panel" style="padding: 20px;">
            <span class="skeleton-box" style="width: 180px; height: 16px; border-radius: 4px; display: block; margin-bottom: 18px;"></span>
            <div style="display: grid; gap: 16px;">
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 100px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 115px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 105px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
                <div class="skeleton-form-field">
                    <span class="skeleton-box" style="width: 90px; height: 13px; border-radius: 3px;"></span>
                    <span class="skeleton-box" style="width: 100%; height: 40px; border-radius: 7px;"></span>
                </div>
            </div>
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
