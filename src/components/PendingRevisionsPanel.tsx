/**
 * Pending Revisions Panel Component
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { PanelBody, PanelRow, Spinner, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { RevisionsList } from './RevisionsList';
import { RevisionStats } from './RevisionStats';
import { useRevisions } from '../hooks/useRevisions';
import type { PluginData, NotificationData } from '../types';

/**
 * Main panel component for the sidebar
 */
export const PendingRevisionsPanel = () => {
    const [notification, setNotification] = useState<NotificationData | null>(null);
    
    // Get plugin data from global
    const data: PluginData = window.dgwPendingRevisionsEditorData || {};
    const {
        postId,
        editingMode: initialEditingMode,
        canAcceptRevisions,
        acceptedRevisionId,
    } = data;

    // Custom hooks
    const {
        revisions,
        isLoading: revisionsLoading,
        error: revisionsError,
        refetch: refetchRevisions,
        approveRevision,
        rejectRevision,
    } = useRevisions(postId);


    // Handle notifications
    useEffect(() => {
        if (revisionsError) {
            setNotification({
                type: 'error',
                message: revisionsError,
                dismissible: true,
            });
        } else {
            setNotification(null);
        }
    }, [revisionsError]);

    /**
     * Handle revision approval
     */
    const handleApproveRevision = async (revisionId: number) => {
        const success = await approveRevision(revisionId);
        if (success) {
            setNotification({
                type: 'success',
                message: __('Revision approved successfully!', 'dgwltd-pending-revisions'),
                dismissible: true,
            });
        }
    };

    /**
     * Handle revision rejection
     */
    const handleRejectRevision = async (revisionId: number) => {
        const success = await rejectRevision(revisionId);
        if (success) {
            setNotification({
                type: 'success',
                message: __('Revision rejected successfully!', 'dgwltd-pending-revisions'),
                dismissible: true,
            });
        }
    };


    /**
     * Handle notification dismissal
     */
    const handleNotificationDismiss = () => {
        setNotification(null);
    };

    // Get pending revisions count
    const pendingRevisions = revisions.filter(revision => 
        revision.is_pending && revision.status !== 'rejected'
    );
    
    const acceptedRevision = revisions.find(revision => revision.is_accepted);

    return (
        <div className="dgw-pending-revisions-panel">
            {/* Notifications */}
            {notification && (
                <Notice
                    status={notification.type}
                    onRemove={notification.dismissible ? handleNotificationDismiss : undefined}
                    isDismissible={notification.dismissible}
                >
                    {notification.message}
                </Notice>
            )}

            {/* Revision Statistics */}
            <PanelBody title={__('Revision Status', 'dgwltd-pending-revisions')} initialOpen={true}>
                <RevisionStats
                    totalRevisions={revisions.length}
                    pendingCount={pendingRevisions.length}
                    acceptedRevision={acceptedRevision}
                    editingMode={initialEditingMode}
                />
            </PanelBody>


            {/* Revisions List */}
            <PanelBody title={__('Revisions', 'dgwltd-pending-revisions')} initialOpen={true}>
                {revisionsLoading ? (
                    <div className="dgw-loading-container">
                        <Spinner />
                        <p>{__('Loading revisions...', 'dgwltd-pending-revisions')}</p>
                    </div>
                ) : revisions.length > 0 ? (
                    <RevisionsList
                        revisions={revisions}
                        acceptedRevisionId={acceptedRevisionId}
                        canAcceptRevisions={canAcceptRevisions}
                        onApprove={handleApproveRevision}
                        onReject={handleRejectRevision}
                        onRefresh={refetchRevisions}
                    />
                ) : (
                    <p className="dgw-no-revisions">
                        {__('No revisions found for this post.', 'dgwltd-pending-revisions')}
                    </p>
                )}
            </PanelBody>

        </div>
    );
};