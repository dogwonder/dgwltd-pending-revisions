/**
 * Save Interceptor Component
 *
 * A hidden component that manages save interception for pending mode posts.
 * This component is rendered but not visible and handles all save operations.
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { useEffect } from '@wordpress/element';
import { useSaveInterceptor } from '../hooks/useSaveInterceptor';
import type { PluginData } from '../types';

interface SaveInterceptorProps {
    data: PluginData;
}

/**
 * Save Interceptor Component
 */
export const SaveInterceptor = ({ data }: SaveInterceptorProps) => {
    const { postId, editingMode, canAcceptRevisions } = data;

    // Initialize save interceptor
    const { isIntercepting } = useSaveInterceptor({
        postId: postId || 0,
        editingMode: editingMode || 'open',
        canAcceptRevisions: canAcceptRevisions || false,
    });

    useEffect(() => {
        if (isIntercepting && process.env.NODE_ENV === 'development') {
            console.log('DGW Pending Revisions: Save interception active for pending mode');
        }
    }, [isIntercepting]);

    // This component doesn't render anything visible
    return null;
};