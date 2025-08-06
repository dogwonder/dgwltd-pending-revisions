/**
 * Main entry point for the Block Editor integration
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

// Import the sidebar plugin
import './sidebar';

// Import the save interceptor plugin
import './saveInterceptorPlugin';

// Import styles
import './styles/editor.scss';

// Import hooks
import { useSaveInterceptor } from './hooks/useSaveInterceptor';

// Register any additional WordPress dependencies
import { registerFormatType } from '@wordpress/rich-text';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

// Log plugin initialization
if (process.env.NODE_ENV === 'development') {
    console.log('DGW Pending Revisions Block Editor integration loaded');
}

// Export types for external use
export * from './types';
export * from './hooks/useRevisions';
export * from './hooks/useEditingMode';
export * from './components/PendingRevisionsPanel';

// Initialize plugin
const initPlugin = () => {
    // Check if we have the required data
    if (!window.dgwPendingRevisionsEditorData) {
        console.warn('DGW Pending Revisions: Editor data not found');
        return;
    }

    const data = window.dgwPendingRevisionsEditorData;
    
    // Log current post info in development
    if (process.env.NODE_ENV === 'development') {
        console.log('DGW Pending Revisions initialized for post:', {
            postId: data.postId,
            postType: data.postType,
            editingMode: data.editingMode,
            canAcceptRevisions: data.canAcceptRevisions,
        });
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPlugin);
} else {
    initPlugin();
}