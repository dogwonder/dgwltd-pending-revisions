/**
 * Custom hook for managing revisions
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import type { Revision, ApiResponse, ApiError, PluginData } from '../types';

/**
 * Hook return type
 */
interface UseRevisionsReturn {
    revisions: Revision[];
    isLoading: boolean;
    error: string | null;
    refetch: () => Promise<void>;
    approveRevision: (revisionId: number) => Promise<boolean>;
    rejectRevision: (revisionId: number) => Promise<boolean>;
}

/**
 * Custom hook for managing revisions
 *
 * @param postId Post ID to fetch revisions for
 * @returns Revision management functions and state
 */
export const useRevisions = (postId: number): UseRevisionsReturn => {
    const [revisions, setRevisions] = useState<Revision[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const data: PluginData = window.dgwPendingRevisionsEditorData || {};

    /**
     * Fetch revisions from API
     */
    const fetchRevisions = useCallback(async (): Promise<void> => {
        if (!postId) return;

        try {
            setIsLoading(true);
            setError(null);

            const response: ApiResponse<Revision[]> = await apiFetch({
                path: `/dgw-pending-revisions/v1/revisions/${postId}`,
                method: 'GET',
            });

            if (response.success && response.data) {
                setRevisions(response.data);
            } else {
                throw new Error(response.message || __('Failed to fetch revisions', 'dgwltd-pending-revisions'));
            }
        } catch (err) {
            const apiError = err as ApiError;
            setError(apiError.message || __('An error occurred while fetching revisions', 'dgwltd-pending-revisions'));
            console.error('Error fetching revisions:', err);
        } finally {
            setIsLoading(false);
        }
    }, [postId]);

    /**
     * Approve a revision
     */
    const approveRevision = useCallback(async (revisionId: number): Promise<boolean> => {
        try {
            const response: ApiResponse = await apiFetch({
                path: `/dgw-pending-revisions/v1/revisions/${postId}/${revisionId}/approve`,
                method: 'POST',
            });

            if (response.success) {
                // Refresh revisions after approval
                await fetchRevisions();
                return true;
            } else {
                throw new Error(response.message || __('Failed to approve revision', 'dgwltd-pending-revisions'));
            }
        } catch (err) {
            const apiError = err as ApiError;
            setError(apiError.message || __('An error occurred while approving revision', 'dgwltd-pending-revisions'));
            console.error('Error approving revision:', err);
            return false;
        }
    }, [postId, fetchRevisions]);

    /**
     * Reject a revision
     */
    const rejectRevision = useCallback(async (revisionId: number): Promise<boolean> => {
        try {
            const response: ApiResponse = await apiFetch({
                path: `/dgw-pending-revisions/v1/revisions/${postId}/${revisionId}/reject`,
                method: 'POST',
            });

            if (response.success) {
                // Refresh revisions after rejection
                await fetchRevisions();
                return true;
            } else {
                throw new Error(response.message || __('Failed to reject revision', 'dgwltd-pending-revisions'));
            }
        } catch (err) {
            const apiError = err as ApiError;
            setError(apiError.message || __('An error occurred while rejecting revision', 'dgwltd-pending-revisions'));
            console.error('Error rejecting revision:', err);
            return false;
        }
    }, [postId, fetchRevisions]);

    // Fetch revisions on mount and when postId changes
    useEffect(() => {
        fetchRevisions();
    }, [fetchRevisions]);

    return {
        revisions,
        isLoading,
        error,
        refetch: fetchRevisions,
        approveRevision,
        rejectRevision,
    };
};