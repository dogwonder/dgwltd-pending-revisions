/**
 * Save Interceptor Hook
 *
 * Handles save interception for pending mode posts to prevent
 * unauthorized publishing while storing changes as pending revisions.
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { useEffect, useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import type { PostData } from '../types';

interface SaveInterceptorOptions {
    postId: number;
    editingMode: string;
    canAcceptRevisions: boolean;
}

/**
 * Hook to intercept saves and handle pending mode
 */
export const useSaveInterceptor = ({ 
    postId, 
    editingMode, 
    canAcceptRevisions 
}: SaveInterceptorOptions) => {
    const { savePost, lockPostSaving, unlockPostSaving } = useDispatch('core/editor');
    const { createNotice } = useDispatch('core/notices');
    
    const { 
        postType,
        isDirty,
        isSaving,
        isAutosaving,
        editedPostContent,
        editedPostTitle,
        editedPostExcerpt,
        getCurrentPost
    } = useSelect((select) => {
        const editor = select('core/editor');
        return {
            postType: editor.getCurrentPostType(),
            isDirty: editor.isEditedPostDirty(),
            isSaving: editor.isSavingPost(),
            isAutosaving: editor.isAutosavingPost(),
            editedPostContent: editor.getEditedPostContent(),
            editedPostTitle: editor.getEditedPostAttribute('title'),
            editedPostExcerpt: editor.getEditedPostAttribute('excerpt'),
            getCurrentPost: editor.getCurrentPost(),
        };
    }, []);

    /**
     * Create a pending revision instead of saving directly
     */
    const createPendingRevision = useCallback(async (postData: PostData) => {
        try {
            const revisionData = {
                post_parent: postId,
                post_title: postData.title,
                post_content: postData.content,
                post_excerpt: postData.excerpt,
                post_type: 'revision',
                post_status: 'inherit',
                meta: {
                    '_dgw_revision_status': 'pending'
                }
            };

            const response = await apiFetch({
                path: '/dgw-pending-revisions/v1/revisions',
                method: 'POST',
                data: revisionData,
            });

            if (response) {
                createNotice(
                    'success',
                    __('Changes saved as pending revision and submitted for approval.', 'dgwltd-pending-revisions'),
                    { 
                        type: 'snackbar',
                        isDismissible: true,
                    }
                );
                return true;
            }
        } catch (error) {
            console.error('Failed to create pending revision:', error);
            createNotice(
                'error',
                __('Failed to save pending revision. Please try again.', 'dgwltd-pending-revisions'),
                { 
                    type: 'snackbar',
                    isDismissible: true,
                }
            );
        }
        return false;
    }, [postId, createNotice]);

    /**
     * Intercept save operations
     */
    const interceptSave = useCallback(async () => {
        // Debug logging
        if (process.env.NODE_ENV === 'development') {
            console.log('DGW Save Interceptor Check:', {
                editingMode,
                canAcceptRevisions,
                isDirty,
                isAutosaving,
                shouldIntercept: editingMode === 'pending' && !canAcceptRevisions
            });
        }
        
        // Only intercept if in pending mode and user can't accept revisions
        if (editingMode !== 'pending' || canAcceptRevisions) {
            if (process.env.NODE_ENV === 'development') {
                console.log('DGW Save Interceptor: Allowing normal save', {
                    reason: editingMode !== 'pending' ? 'Not in pending mode' : 'User can accept revisions'
                });
            }
            return true; // Allow normal save
        }

        // Skip if autosaving
        if (isAutosaving) {
            return true;
        }

        // Check if there are actually changes to save
        if (!isDirty) {
            return true;
        }

        // Lock saving to prevent default save
        lockPostSaving();

        // Create pending revision with current content
        const success = await createPendingRevision({
            title: editedPostTitle,
            content: editedPostContent,
            excerpt: editedPostExcerpt,
        });

        if (success) {
            // Mark the editor as clean since we've "saved" the changes
            // This prevents the "unsaved changes" warning
            setTimeout(() => {
                unlockPostSaving();
            }, 100);
        } else {
            unlockPostSaving();
        }

        return false; // Prevent default save
    }, [
        editingMode,
        canAcceptRevisions,
        isAutosaving,
        isDirty,
        editedPostTitle,
        editedPostContent,
        editedPostExcerpt,
        lockPostSaving,
        unlockPostSaving,
        createPendingRevision
    ]);

    /**
     * Override the save function
     */
    useEffect(() => {
        // Only set up interception if in pending mode and user can't accept revisions
        if (editingMode !== 'pending' || canAcceptRevisions) {
            return;
        }

        // Listen for save attempts
        const handleKeyDown = (event: KeyboardEvent) => {
            // Intercept Ctrl+S / Cmd+S
            if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                event.preventDefault();
                event.stopPropagation();
                interceptSave();
            }
        };

        document.addEventListener('keydown', handleKeyDown, true);

        return () => {
            document.removeEventListener('keydown', handleKeyDown, true);
        };
    }, [editingMode, canAcceptRevisions, interceptSave]);

    /**
     * Hook into WordPress save actions
     */
    useEffect(() => {
        if (editingMode !== 'pending' || canAcceptRevisions) {
            return;
        }

        // Override save button clicks
        const handleSaveClick = (event: Event) => {
            const target = event.target as HTMLElement;
            
            // Check if this is a save button
            if (
                target.classList.contains('editor-post-save-draft') ||
                target.classList.contains('editor-post-publish-button') ||
                target.classList.contains('editor-post-publish-panel__toggle')
            ) {
                event.preventDefault();
                event.stopPropagation();
                interceptSave();
            }
        };

        document.addEventListener('click', handleSaveClick, true);

        return () => {
            document.removeEventListener('click', handleSaveClick, true);
        };
    }, [editingMode, canAcceptRevisions, interceptSave]);

    return {
        isIntercepting: editingMode === 'pending' && !canAcceptRevisions,
        interceptSave,
    };
};