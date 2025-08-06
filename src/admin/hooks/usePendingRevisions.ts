/**
 * Hook for managing pending revisions
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import type { Revision } from '../../types';

/**
 * Hook return type
 */
interface UsePendingRevisionsReturn {
    revisions: Revision[];
    isLoading: boolean;
    error: string | null;
    refetch: () => Promise<void>;
    approveRevision: (revisionId: number) => Promise<void>;
    rejectRevision: (revisionId: number) => Promise<void>;
}

/**
 * Custom hook for managing pending revisions
 */
export const usePendingRevisions = (): UsePendingRevisionsReturn => {
    const [revisions, setRevisions] = useState<Revision[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    /**
     * Fetch pending revisions from API
     */
    const fetchRevisions = async (): Promise<void> => {
        try {
            setIsLoading(true);
            setError(null);

            const response = await apiFetch<Revision[]>({
                path: '/dgw-pending-revisions/v1/revisions/pending',
                method: 'GET',
            });

            setRevisions(response);
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : __('Failed to fetch pending revisions', 'dgwltd-pending-revisions');
            setError(errorMessage);
            console.error('Error fetching pending revisions:', err);
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * Approve a revision
     */
    const approveRevision = useCallback(async (revisionId: number): Promise<void> => {
        try {
            await apiFetch({
                path: `/dgw-pending-revisions/v1/revisions/${revisionId}/approve`,
                method: 'POST',
            });

            // Remove the approved revision from the list
            setRevisions(prev => prev.filter(revision => revision.id !== revisionId));
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : __('Failed to approve revision', 'dgwltd-pending-revisions');
            setError(errorMessage);
            console.error('Error approving revision:', err);
            throw err;
        }
    }, []);

    /**
     * Reject a revision
     */
    const rejectRevision = useCallback(async (revisionId: number): Promise<void> => {
        try {
            await apiFetch({
                path: `/dgw-pending-revisions/v1/revisions/${revisionId}/reject`,
                method: 'POST',
            });

            // Remove the rejected revision from the list
            setRevisions(prev => prev.filter(revision => revision.id !== revisionId));
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : __('Failed to reject revision', 'dgwltd-pending-revisions');
            setError(errorMessage);
            console.error('Error rejecting revision:', err);
            throw err;
        }
    }, []);

    /**
     * Refetch revisions manually
     */
    const refetch = useCallback(async (): Promise<void> => {
        await fetchRevisions();
    }, []);

    // Fetch revisions on mount
    useEffect(() => {
        fetchRevisions();
    }, []);

    return {
        revisions,
        isLoading,
        error,
        refetch,
        approveRevision,
        rejectRevision,
    };
};