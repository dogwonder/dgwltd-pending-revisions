/**
 * Revision Stats Component
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { Flex, FlexItem, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { backup, check, info, lock, unlock } from '@wordpress/icons';
import type { Revision, EditingMode } from '../types';

/**
 * Component props
 */
interface RevisionStatsProps {
    totalRevisions: number;
    pendingCount: number;
    acceptedRevision?: Revision;
    editingMode: EditingMode;
}

/**
 * Revision statistics component
 */
export const RevisionStats = ({
    totalRevisions,
    pendingCount,
    acceptedRevision,
    editingMode,
}: RevisionStatsProps) => {
    
    /**
     * Get editing mode info
     */
    const getModeInfo = (mode: EditingMode) => {
        switch (mode) {
            case 'open':
                return {
                    icon: unlock,
                    label: __('Open', 'dgwltd-pending-revisions'),
                    className: 'dgw-mode-open',
                };
            case 'pending':
                return {
                    icon: info,
                    label: __('Requires Approval', 'dgwltd-pending-revisions'),
                    className: 'dgw-mode-pending',
                };
            case 'locked':
                return {
                    icon: lock,
                    label: __('Locked', 'dgwltd-pending-revisions'),
                    className: 'dgw-mode-locked',
                };
            default:
                return {
                    icon: unlock,
                    label: __('Unknown', 'dgwltd-pending-revisions'),
                    className: 'dgw-mode-unknown',
                };
        }
    };

    /**
     * Format date for display
     */
    const formatDate = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleDateString(undefined, {
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
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now.getTime() - date.getTime();
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffHours / 24);

        if (diffHours < 1) {
            return __('Less than an hour ago', 'dgwltd-pending-revisions');
        } else if (diffHours < 24) {
            return `${diffHours} ${__('hours ago', 'dgwltd-pending-revisions')}`;
        } else if (diffDays === 1) {
            return __('1 day ago', 'dgwltd-pending-revisions');
        } else {
            return `${diffDays} ${__('days ago', 'dgwltd-pending-revisions')}`;
        }
    };

    const modeInfo = getModeInfo(editingMode);

    return (
        <div className="dgw-revision-stats">
            <Flex direction="column" gap={3}>
                {/* Editing Mode */}
                <FlexItem>
                    <div className={`dgw-stat-item ${modeInfo.className}`}>
                        <Flex align="center" gap={2}>
                            <FlexItem>
                                <Icon icon={modeInfo.icon} size={20} />
                            </FlexItem>
                            <FlexItem>
                                <div className="dgw-stat-content">
                                    <span className="dgw-stat-label">
                                        {__('Editing Mode', 'dgwltd-pending-revisions')}
                                    </span>
                                    <span className="dgw-stat-value">
                                        {modeInfo.label}
                                    </span>
                                </div>
                            </FlexItem>
                        </Flex>
                    </div>
                </FlexItem>

                {/* Total Revisions */}
                <FlexItem>
                    <div className="dgw-stat-item">
                        <Flex align="center" gap={2}>
                            <FlexItem>
                                <Icon icon={backup} size={20} />
                            </FlexItem>
                            <FlexItem>
                                <div className="dgw-stat-content">
                                    <span className="dgw-stat-label">
                                        {__('Total Revisions', 'dgwltd-pending-revisions')}
                                    </span>
                                    <span className="dgw-stat-value">
                                        {totalRevisions}
                                    </span>
                                </div>
                            </FlexItem>
                        </Flex>
                    </div>
                </FlexItem>

                {/* Pending Revisions */}
                {pendingCount > 0 && (
                    <FlexItem>
                        <div className="dgw-stat-item dgw-stat-pending">
                            <Flex align="center" gap={2}>
                                <FlexItem>
                                    <Icon icon={info} size={20} />
                                </FlexItem>
                                <FlexItem>
                                    <div className="dgw-stat-content">
                                        <span className="dgw-stat-label">
                                            {__('Pending Approval', 'dgwltd-pending-revisions')}
                                        </span>
                                        <span className="dgw-stat-value dgw-stat-highlight">
                                            {pendingCount}
                                        </span>
                                    </div>
                                </FlexItem>
                            </Flex>
                        </div>
                    </FlexItem>
                )}

                {/* Currently Published */}
                {acceptedRevision && (
                    <FlexItem>
                        <div className="dgw-stat-item dgw-stat-published">
                            <Flex align="center" gap={2}>
                                <FlexItem>
                                    <Icon icon={check} size={20} />
                                </FlexItem>
                                <FlexItem>
                                    <div className="dgw-stat-content">
                                        <span className="dgw-stat-label">
                                            {__('Currently Published', 'dgwltd-pending-revisions')}
                                        </span>
                                        <span className="dgw-stat-value">
                                            {__('By', 'dgwltd-pending-revisions')} {acceptedRevision.author_name}
                                        </span>
                                        <span className="dgw-stat-meta" title={formatDate(acceptedRevision.date)}>
                                            {getTimeAgo(acceptedRevision.date)}
                                        </span>
                                    </div>
                                </FlexItem>
                            </Flex>
                        </div>
                    </FlexItem>
                )}

                {/* No revisions state */}
                {totalRevisions === 0 && (
                    <FlexItem>
                        <div className="dgw-stat-item dgw-stat-empty">
                            <p className="description">
                                {__('No revisions have been created for this post yet.', 'dgwltd-pending-revisions')}
                            </p>
                        </div>
                    </FlexItem>
                )}
            </Flex>
        </div>
    );
};