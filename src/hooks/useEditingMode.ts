/**
 * Custom hook for managing editing mode
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import type { EditingMode, ApiResponse, ApiError, PluginData } from '../types';

/**
 * Hook return type
 */
interface UseEditingModeReturn {
    editingMode: EditingMode;
    isUpdating: boolean;
    error: string | null;
    updateEditingMode: (mode: EditingMode) => Promise<boolean>;
}

/**
 * Custom hook for managing editing mode
 *
 * @param postId Post ID
 * @param initialMode Initial editing mode
 * @returns Editing mode management functions and state
 */
export const useEditingMode = (postId: number, initialMode: EditingMode): UseEditingModeReturn => {
    const [editingMode, setEditingMode] = useState<EditingMode>(initialMode);
    const [isUpdating, setIsUpdating] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const data: PluginData = window.dgwPendingRevisionsEditorData || {};

    /**
     * Update editing mode
     */
    const updateEditingMode = useCallback(async (mode: EditingMode): Promise<boolean> => {
        if (!postId || mode === editingMode) return true;

        try {
            setIsUpdating(true);
            setError(null);

            const response: ApiResponse<{ editing_mode: EditingMode }> = await apiFetch({
                path: `/dgw-pending-revisions/v1/posts/${postId}/editing-mode`,
                method: 'PUT',
                data: {
                    editing_mode: mode,
                },
            });

            if (response.success && response.data) {
                setEditingMode(response.data.editing_mode);
                return true;
            } else {
                throw new Error(response.message || __('Failed to update editing mode', 'dgwltd-pending-revisions'));
            }
        } catch (err) {
            const apiError = err as ApiError;
            setError(apiError.message || __('An error occurred while updating editing mode', 'dgwltd-pending-revisions'));
            console.error('Error updating editing mode:', err);
            return false;
        } finally {
            setIsUpdating(false);
        }
    }, [postId, editingMode]);

    return {
        editingMode,
        isUpdating,
        error,
        updateEditingMode,
    };
};