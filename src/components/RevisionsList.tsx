/**
 * Revisions List Component
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { Button, ButtonGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { RevisionListItem } from './RevisionListItem';
import type { Revision } from '../types';

/**
 * Component props
 */
interface RevisionsListProps {
    revisions: Revision[];
    acceptedRevisionId: number | false;
    canAcceptRevisions: boolean;
    onApprove: (revisionId: number) => Promise<void>;
    onReject: (revisionId: number) => Promise<void>;
    onRefresh: () => Promise<void>;
}

/**
 * Filter types for revisions
 */
type FilterType = 'all' | 'pending' | 'accepted' | 'rejected';

/**
 * Revisions list component
 */
export const RevisionsList = ({
    revisions,
    acceptedRevisionId,
    canAcceptRevisions,
    onApprove,
    onReject,
    onRefresh,
}: RevisionsListProps) => {
    const [filter, setFilter] = useState<FilterType>('all');
    const [isRefreshing, setIsRefreshing] = useState(false);

    /**
     * Filter revisions based on current filter
     */
    const getFilteredRevisions = (): Revision[] => {
        switch (filter) {
            case 'pending':
                return revisions.filter(revision => 
                    revision.is_pending && revision.status !== 'rejected'
                );
            case 'accepted':
                return revisions.filter(revision => revision.is_accepted);
            case 'rejected':
                return revisions.filter(revision => revision.status === 'rejected');
            default:
                return revisions;
        }
    };

    /**
     * Handle refresh button click
     */
    const handleRefresh = async () => {
        setIsRefreshing(true);
        await onRefresh();
        setIsRefreshing(false);
    };

    /**
     * Handle revision view (navigate to revision comparison)
     */
    const handleViewRevision = (revisionId: number) => {
        const url = new URL(window.location.href);
        url.searchParams.set('revision', revisionId.toString());
        window.open(url.toString(), '_blank');
    };

    const filteredRevisions = getFilteredRevisions();
    const pendingCount = revisions.filter(r => r.is_pending && r.status !== 'rejected').length;
    const acceptedCount = revisions.filter(r => r.is_accepted).length;
    const rejectedCount = revisions.filter(r => r.status === 'rejected').length;

    return (
        <div className="dgw-revisions-list">
            {/* Filter buttons */}
            <div className="dgw-revisions-filters">
                <ButtonGroup>
                    <Button
                        isSmall
                        variant={filter === 'all' ? 'primary' : 'secondary'}
                        onClick={() => setFilter('all')}
                    >
                        {__('All', 'dgwltd-pending-revisions')} ({revisions.length})
                    </Button>
                    {pendingCount > 0 && (
                        <Button
                            isSmall
                            variant={filter === 'pending' ? 'primary' : 'secondary'}
                            onClick={() => setFilter('pending')}
                        >
                            {__('Pending', 'dgwltd-pending-revisions')} ({pendingCount})
                        </Button>
                    )}
                    {acceptedCount > 0 && (
                        <Button
                            isSmall
                            variant={filter === 'accepted' ? 'primary' : 'secondary'}
                            onClick={() => setFilter('accepted')}
                        >
                            {__('Accepted', 'dgwltd-pending-revisions')} ({acceptedCount})
                        </Button>
                    )}
                    {rejectedCount > 0 && (
                        <Button
                            isSmall
                            variant={filter === 'rejected' ? 'primary' : 'secondary'}
                            onClick={() => setFilter('rejected')}
                        >
                            {__('Rejected', 'dgwltd-pending-revisions')} ({rejectedCount})
                        </Button>
                    )}
                </ButtonGroup>
            </div>

            {/* Refresh button */}
            <div className="dgw-revisions-actions">
                <Button
                    isSmall
                    variant="tertiary"
                    isBusy={isRefreshing}
                    onClick={handleRefresh}
                >
                    {__('Refresh', 'dgwltd-pending-revisions')}
                </Button>
            </div>

            {/* Revisions list */}
            <div className="dgw-revisions-items">
                {filteredRevisions.length > 0 ? (
                    filteredRevisions.map((revision) => (
                        <RevisionListItem
                            key={revision.id}
                            revision={revision}
                            isAccepted={revision.id === acceptedRevisionId}
                            canAcceptRevisions={canAcceptRevisions}
                            onApprove={onApprove}
                            onReject={onReject}
                            onView={handleViewRevision}
                        />
                    ))
                ) : (
                    <div className="dgw-no-revisions-filtered">
                        {filter === 'all' ? (
                            <p>{__('No revisions found.', 'dgwltd-pending-revisions')}</p>
                        ) : (
                            <p>
                                {filter === 'pending' && __('No pending revisions.', 'dgwltd-pending-revisions')}
                                {filter === 'accepted' && __('No accepted revisions.', 'dgwltd-pending-revisions')}
                                {filter === 'rejected' && __('No rejected revisions.', 'dgwltd-pending-revisions')}
                            </p>
                        )}
                    </div>
                )}
            </div>

            {/* Help text */}
            <div className="dgw-revisions-help">
                <p className="description">
                    {canAcceptRevisions ? (
                        __('Click on a revision to view details, or use the approve/reject buttons to manage pending revisions.', 'dgwltd-pending-revisions')
                    ) : (
                        __('Contact an editor to approve or reject pending revisions.', 'dgwltd-pending-revisions')
                    )}
                </p>
            </div>
        </div>
    );
};