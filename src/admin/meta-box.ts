/**
 * Meta Box Quick Switch Functionality
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Quick switch interface
 */
interface QuickSwitchData {
    postId: number;
    revisionId: number;
    reason?: string;
}

/**
 * API Response interface
 */
interface QuickSwitchResponse {
    success: boolean;
    data?: {
        post_id: number;
        revision_id: number;
        previous_revision_id: number;
        reason: string;
    };
    message?: string;
}

/**
 * Initialize meta box quick switch functionality
 */
const initMetaBoxQuickSwitch = (): void => {
    const quickSwitchButtons = document.querySelectorAll<HTMLButtonElement>('.dgw-quick-switch-btn');
    
    quickSwitchButtons.forEach(button => {
        button.addEventListener('click', handleQuickSwitch);
    });
};

/**
 * Handle quick switch button click
 */
const handleQuickSwitch = async (event: Event): Promise<void> => {
    event.preventDefault();
    
    const button = event.target as HTMLButtonElement;
    const postId = parseInt(button.dataset.postId || '0', 10);
    const revisionId = parseInt(button.dataset.revisionId || '0', 10);
    const revisionStatus = button.dataset.revisionStatus || 'pending';
    const originalText = button.innerHTML;
    
    if (!postId || !revisionId) {
        console.error('Missing post ID or revision ID');
        return;
    }
    
    // Smart confirmation based on revision status and age
    const confirmationMessage = getConfirmationMessage(revisionStatus, button);
    if (confirmationMessage) {
        const confirmed = confirm(confirmationMessage);
        if (!confirmed) {
            return;
        }
    }
    
    // Update button state
    updateButtonState(button, 'loading', `🔄 ${__('Publishing...', 'dgwltd-pending-revisions')}`);
    
    try {
        const response = await quickSwitchRevision({
            postId,
            revisionId,
            reason: 'Quick switch from meta box'
        });
        
        if (response.success) {
            // Success state
            updateButtonState(button, 'success', `✅ ${__('Published!', 'dgwltd-pending-revisions')}`);
            
            // Show success notice
            showNotice('success', __('Revision published successfully! Page will refresh to show changes.', 'dgwltd-pending-revisions'));
            
            // Delayed reload with proper cleanup (simplified to avoid DOM issues)
            setTimeout(() => {
                // Use location.assign instead of reload to avoid connection issues
                window.location.assign(window.location.href);
            }, 2000);
        } else {
            throw new Error(response.message || __('Failed to publish revision', 'dgwltd-pending-revisions'));
        }
    } catch (error) {
        // Error state
        const errorMessage = error instanceof Error ? error.message : __('Failed to publish revision', 'dgwltd-pending-revisions');
        updateButtonState(button, 'error', `❌ ${__('Failed - Retry', 'dgwltd-pending-revisions')}`);
        
        // Show error notice
        showNotice('error', errorMessage);
        
        // Reset button after delay
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
            button.className = button.className.replace(/\s*(loading|error|success)/g, '');
        }, 3000);
    }
};

/**
 * Make API call to switch revision
 */
const quickSwitchRevision = async (data: QuickSwitchData): Promise<QuickSwitchResponse> => {
    return await apiFetch({
        path: `/dgw-pending-revisions/v1/posts/${data.postId}/set-published-revision`,
        method: 'POST',
        data: {
            revision_id: data.revisionId,
            reason: data.reason
        }
    }) as QuickSwitchResponse;
};

/**
 * Get confirmation message based on revision status and age
 */
const getConfirmationMessage = (revisionStatus: string, button: HTMLButtonElement): string | null => {
    // Always confirm for rejected revisions
    if (revisionStatus === 'rejected') {
        return __('This revision was previously rejected. Are you sure you want to republish it?', 'dgwltd-pending-revisions');
    }
    
    // Check age for pending revisions
    if (isOldRevision(button)) {
        return __('This revision is more than 24 hours old. Are you sure you want to publish it?', 'dgwltd-pending-revisions');
    }
    
    // No confirmation needed for recent pending revisions
    return null;
};

/**
 * Check if revision is old (>24 hours)
 */
const isOldRevision = (button: HTMLButtonElement): boolean => {
    const row = button.closest('tr');
    if (!row) return false;
    
    const dateCell = row.querySelector('td:nth-child(2)');
    if (!dateCell) return false;
    
    const dateText = dateCell.textContent?.trim() || '';
    const now = new Date();
    const oneDayAgo = new Date(now.getTime() - 24 * 60 * 60 * 1000);
    
    // Simple check - if date doesn't contain today's month/day, consider it potentially old
    const today = now.toDateString().slice(4, 10); // Get "Jan 15" format
    return !dateText.includes(today);
};

/**
 * Update table row to reflect new published state
 */
const updateTableRowState = (button: HTMLButtonElement, publishedRevisionId?: number): void => {
    const table = button.closest('table');
    if (!table) return;
    
    const currentRow = button.closest('tr');
    if (!currentRow) return;
    
    // Find all revision rows in the table
    const allRows = table.querySelectorAll('tr');
    
    allRows.forEach(row => {
        const rowButton = row.querySelector('.dgw-quick-switch-btn') as HTMLButtonElement;
        const statusBadge = row.querySelector('.dgw-status-badge');
        const actions = row.querySelector('.dgw-revision-actions');
        
        if (row === currentRow) {
            // This is now the published revision
            if (statusBadge) {
                statusBadge.className = 'dgw-status-badge dgw-status-published';
                statusBadge.textContent = __('Published', 'dgwltd-pending-revisions');
            }
            
            if (actions && rowButton) {
                // Replace button with "Current" badge
                actions.innerHTML = `
                    <span class="dgw-current-badge">✓ ${__('Current', 'dgwltd-pending-revisions')}</span>
                    <a href="${rowButton.getAttribute('href') || '#'}" class="button button-small" target="_blank" rel="noopener noreferrer">
                        ${__('Compare', 'dgwltd-pending-revisions')}
                    </a>
                `;
            }
        } else if (rowButton) {
            // Other rows - re-enable buttons that were current
            const badge = row.querySelector('.dgw-current-badge');
            if (badge && statusBadge) {
                // This was previously current, now make it switchable
                const revisionId = rowButton.dataset.revisionId;
                const revisionStatus = rowButton.dataset.revisionStatus || 'pending';
                
                statusBadge.className = `dgw-status-badge dgw-status-${revisionStatus}`;
                statusBadge.textContent = revisionStatus.charAt(0).toUpperCase() + revisionStatus.slice(1);
                
                // Recreate the button
                const buttonHtml = revisionStatus === 'rejected' 
                    ? `🔄 ${__('Republish', 'dgwltd-pending-revisions')}`
                    : `${__('Set as Published', 'dgwltd-pending-revisions')}`;
                    
                const buttonClass = revisionStatus === 'rejected' ? 'button-secondary' : 'button-primary';
                
                if (actions) {
                    actions.innerHTML = `
                        <button type="button" 
                                class="button button-small dgw-quick-switch-btn ${buttonClass}" 
                                data-post-id="${rowButton.dataset.postId}"
                                data-revision-id="${revisionId}"
                                data-revision-status="${revisionStatus}">
                            ${buttonHtml}
                        </button>
                        <a href="${window.location.origin}/wp-admin/revision.php?revision=${revisionId}" 
                           class="button button-small" 
                           target="_blank" 
                           rel="noopener noreferrer">
                            ${__('Compare', 'dgwltd-pending-revisions')}
                        </a>
                    `;
                    
                    // Re-attach event listener to new button
                    const newButton = actions.querySelector('.dgw-quick-switch-btn') as HTMLButtonElement;
                    if (newButton) {
                        newButton.addEventListener('click', handleQuickSwitch);
                    }
                }
            }
        }
    });
};

/**
 * Update button state
 */
const updateButtonState = (button: HTMLButtonElement, state: 'loading' | 'success' | 'error', text: string): void => {
    // Remove existing state classes
    button.className = button.className.replace(/\s*(loading|error|success)/g, '');
    
    // Add new state
    button.classList.add(state);
    button.innerHTML = text;
    button.disabled = state === 'loading';
};

/**
 * Show admin notice
 */
const showNotice = (type: 'success' | 'error', message: string): void => {
    const notice = document.createElement('div');
    notice.className = `notice notice-${type} is-dismissible`;
    notice.innerHTML = `<p>${message}</p>`;
    
    // Insert after the main heading
    const heading = document.querySelector('.wrap h1');
    if (heading && heading.parentNode) {
        heading.parentNode.insertBefore(notice, heading.nextSibling);
        
        // Auto-remove success notices
        if (type === 'success') {
            setTimeout(() => {
                notice.remove();
            }, 3000);
        }
    }
};

/**
 * Initialize when DOM is ready
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMetaBoxQuickSwitch);
} else {
    initMetaBoxQuickSwitch();
}