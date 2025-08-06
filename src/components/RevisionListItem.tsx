/**
 * Revision List Item Component
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { Button, ButtonGroup, Flex, FlexItem, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { check, close, seen, backup } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import type { RevisionListItemProps } from '../types';

/**
 * Individual revision list item component
 */
export const RevisionListItem = ({
    revision,
    isAccepted,
    canAcceptRevisions,
    onApprove,
    onReject,
    onView,
}: RevisionListItemProps) => {
    const [isApproving, setIsApproving] = useState(false);
    const [isRejecting, setIsRejecting] = useState(false);

    /**
     * Handle approve button click
     */
    const handleApprove = async () => {
        if (isApproving || isRejecting) return;
        
        // Confirm for rejected revisions (republishing)
        if (revision.status === 'rejected') {
            const confirmed = confirm(__('This revision was previously rejected. Are you sure you want to republish it?', 'dgwltd-pending-revisions'));
            if (!confirmed) {
                return;
            }
        }
        
        setIsApproving(true);
        try {
            await onApprove(revision.id);
        } finally {
            setIsApproving(false);
        }
    };

    /**
     * Handle reject button click
     */
    const handleReject = async () => {
        if (isApproving || isRejecting) return;
        
        setIsRejecting(true);
        try {
            await onReject(revision.id);
        } finally {
            setIsRejecting(false);
        }
    };


    /**
     * Get status badge component
     */
    const getStatusBadge = () => {
        if (isAccepted) {
            return (
                <span className="dgw-revision-status dgw-revision-status--accepted">
                    <Icon icon={check} size={16} />
                    {__('Published', 'dgwltd-pending-revisions')}
                </span>
            );
        }

        if (revision.status === 'rejected') {
            return (
                <span className="dgw-revision-status dgw-revision-status--rejected">
                    <Icon icon={close} size={16} />
                    {__('Rejected', 'dgwltd-pending-revisions')}
                </span>
            );
        }

        return (
            <span className="dgw-revision-status dgw-revision-status--pending">
                <Icon icon={backup} size={16} />
                {__('Pending', 'dgwltd-pending-revisions')}
            </span>
        );
    };

    /**
     * Format date for display
     */
    const formatDate = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    /**
     * Get time ago string
     */
    const getTimeAgo = (dateString: string): string => {
        // WordPress returns dates in GMT - ensure proper parsing
        const date = new Date(dateString + (dateString.includes('Z') ? '' : 'Z'));
        const now = new Date();
        const diffMs = now.getTime() - date.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 1) {
            return __('Just now', 'dgwltd-pending-revisions');
        } else if (diffMins < 60) {
            return `${diffMins} ${__('minutes ago', 'dgwltd-pending-revisions')}`;
        } else if (diffHours < 24) {
            return `${diffHours} ${__('hours ago', 'dgwltd-pending-revisions')}`;
        } else {
            return `${diffDays} ${__('days ago', 'dgwltd-pending-revisions')}`;
        }
    };

    // Can manage pending revisions or republish rejected ones
    const canManagePending = canAcceptRevisions && !isAccepted && revision.status !== 'rejected';
    const canRepublish = canAcceptRevisions && !isAccepted && revision.status === 'rejected';

    return (
        <div className="dgw-revision-item">
            <Flex direction="column" gap={2}>
                {/* Header with status and date */}
                <FlexItem>
                    <Flex justify="space-between" align="center">
                        <FlexItem>
                            {getStatusBadge()}
                        </FlexItem>
                        <FlexItem>
                            <span className="dgw-revision-date" title={formatDate(revision.date)}>
                                {getTimeAgo(revision.date)}
                            </span>
                        </FlexItem>
                    </Flex>
                </FlexItem>

                {/* Author and revision info */}
                <FlexItem>
                    <div className="dgw-revision-meta">
                        <strong>{__('Author:', 'dgwltd-pending-revisions')}</strong> {revision.author_name}
                    </div>
                    {revision.title.rendered && (
                        <div className="dgw-revision-title">
                            <strong>{__('Title:', 'dgwltd-pending-revisions')}</strong> 
                            <span title={revision.title.rendered}>
                                {revision.title.rendered.length > 50 
                                    ? `${revision.title.rendered.substring(0, 50)}...`
                                    : revision.title.rendered
                                }
                            </span>
                        </div>
                    )}
                    <div className="dgw-revision-version">
                        <strong>{__('Version: ', 'dgwltd-pending-revisions')}</strong> 
                            <a
                                href={`${window.location.origin}/wp-admin/revision.php?revision=${revision.id}`}
                                target="_blank"
                                rel="noopener noreferrer"
                            >#{revision.id}
                            </a>
                    </div>
                </FlexItem>

                {/* Actions */}
                <FlexItem>
                    <Flex justify="space-between" align="center">
                        {canManagePending && (
                            <FlexItem>
                                <ButtonGroup>
                                    <Button
                                        isSmall
                                        variant="primary"
                                        icon={check}
                                        isBusy={isApproving}
                                        disabled={isRejecting}
                                        onClick={handleApprove}
                                    >
                                        {__('Approve', 'dgwltd-pending-revisions')}
                                    </Button>
                                    <Button
                                        isSmall
                                        variant="secondary"
                                        icon={close}
                                        isBusy={isRejecting}
                                        disabled={isApproving}
                                        onClick={handleReject}
                                    >
                                        {__('Reject', 'dgwltd-pending-revisions')}
                                    </Button>
                                </ButtonGroup>
                            </FlexItem>
                        )}
                        
                        {canRepublish && (
                            <FlexItem>
                                <Button
                                    isSmall
                                    variant="secondary"
                                    icon={backup}
                                    isBusy={isApproving}
                                    disabled={isRejecting}
                                    onClick={handleApprove}
                                >
                                    {__('Republish', 'dgwltd-pending-revisions')}
                                </Button>
                            </FlexItem>
                        )}
                    </Flex>
                </FlexItem>
            </Flex>
        </div>
    );
};