/**
 * Pending Revisions List Component for Admin Dashboard
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { 
    Button, 
    ButtonGroup, 
    Flex, 
    FlexItem, 
    Icon,
    SelectControl,
    SearchControl,
    Spinner,
    __experimentalText as Text,
    __experimentalHeading as Heading
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { check, close, seen } from '@wordpress/icons';
import { usePendingRevisions } from '../hooks/usePendingRevisions';
import type { Revision } from '../../types';

/**
 * Individual revision item component
 */
interface RevisionItemProps {
    revision: Revision;
    onApprove: (id: number) => Promise<void>;
    onReject: (id: number) => Promise<void>;
    onView: (id: number) => void;
}

const RevisionItem = ({ revision, onApprove, onReject, onView }: RevisionItemProps) => {
    const [isApproving, setIsApproving] = useState(false);
    const [isRejecting, setIsRejecting] = useState(false);

    const handleApprove = async () => {
        setIsApproving(true);
        try {
            await onApprove(revision.id);
        } finally {
            setIsApproving(false);
        }
    };

    const handleReject = async () => {
        setIsRejecting(true);
        try {
            await onReject(revision.id);
        } finally {
            setIsRejecting(false);
        }
    };

    const formatDate = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <div className="dgw-revision-item">
            <Flex justify="space-between" align="center" gap={4}>
                <FlexItem>
                    <div className="dgw-revision-info">
                        <Heading level={4} className="dgw-revision-title">
                            {revision.title.rendered || __('(No title)', 'dgwltd-pending-revisions')}
                        </Heading>
                        <Text className="dgw-revision-meta">
                            {__('By', 'dgwltd-pending-revisions')} <strong>{revision.author_name}</strong> • {formatDate(revision.date)}
                        </Text>
                    </div>
                </FlexItem>
                
                <FlexItem>
                    <ButtonGroup>
                        <Button
                            variant="tertiary"
                            size="small"
                            icon={seen}
                            onClick={() => onView(revision.id)}
                        >
                            {__('View', 'dgwltd-pending-revisions')}
                        </Button>
                        
                        <Button
                            variant="primary"
                            size="small"
                            icon={check}
                            isBusy={isApproving}
                            disabled={isRejecting}
                            onClick={handleApprove}
                        >
                            {__('Approve', 'dgwltd-pending-revisions')}
                        </Button>
                        
                        <Button
                            variant="secondary"
                            size="small"
                            icon={close}
                            isBusy={isRejecting}
                            disabled={isApproving}
                            onClick={handleReject}
                        >
                            {__('Reject', 'dgwltd-pending-revisions')}
                        </Button>
                    </ButtonGroup>
                </FlexItem>
            </Flex>
        </div>
    );
};

/**
 * Main pending revisions list component
 */
export const PendingRevisionsList = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [postTypeFilter, setPostTypeFilter] = useState('');
    const [authorFilter, setAuthorFilter] = useState('');
    
    const {
        revisions,
        isLoading,
        error,
        refetch,
        approveRevision,
        rejectRevision,
    } = usePendingRevisions();

    /**
     * Filter revisions based on search and filters
     */
    const getFilteredRevisions = (): Revision[] => {
        let filtered = revisions;

        // Search filter
        if (searchTerm) {
            filtered = filtered.filter(revision =>
                revision.title.rendered.toLowerCase().includes(searchTerm.toLowerCase()) ||
                revision.author_name.toLowerCase().includes(searchTerm.toLowerCase())
            );
        }

        // Post type filter
        if (postTypeFilter) {
            // This would need to be implemented based on post type data
            // filtered = filtered.filter(revision => revision.post_type === postTypeFilter);
        }

        // Author filter
        if (authorFilter) {
            filtered = filtered.filter(revision => 
                revision.author.toString() === authorFilter
            );
        }

        return filtered;
    };

    /**
     * Handle revision view
     */
    const handleViewRevision = (revisionId: number) => {
        // Navigate to revision comparison page
        const url = new URL(window.location.origin + '/wp-admin/revision.php');
        url.searchParams.set('revision', revisionId.toString());
        window.open(url.toString(), '_blank');
    };


    const filteredRevisions = getFilteredRevisions();

    if (isLoading) {
        return (
            <div className="dgw-loading-container">
                <Flex justify="center" align="center" gap={3}>
                    <FlexItem>
                        <Spinner />
                    </FlexItem>
                    <FlexItem>
                        <Text>{__('Loading pending revisions...', 'dgwltd-pending-revisions')}</Text>
                    </FlexItem>
                </Flex>
            </div>
        );
    }

    if (error) {
        return (
            <div className="dgw-error-container">
                <Text>{error}</Text>
                <Button variant="secondary" onClick={() => refetch()}>
                    {__('Try Again', 'dgwltd-pending-revisions')}
                </Button>
            </div>
        );
    }

    return (
        <div className="dgw-pending-revisions-list">
            {/* Filters */}
            <div className="dgw-list-filters">
                <Flex gap={4} wrap>
                    <FlexItem isBlock>
                        <SearchControl
                            value={searchTerm}
                            onChange={setSearchTerm}
                            placeholder={__('Search revisions...', 'dgwltd-pending-revisions')}
                        />
                    </FlexItem>
                    
                    <FlexItem>
                        <SelectControl
                            value={postTypeFilter}
                            onChange={setPostTypeFilter}
                            options={[
                                { label: __('All Post Types', 'dgwltd-pending-revisions'), value: '' },
                                { label: __('Posts', 'dgwltd-pending-revisions'), value: 'post' },
                                { label: __('Pages', 'dgwltd-pending-revisions'), value: 'page' },
                            ]}
                        />
                    </FlexItem>
                    
                </Flex>
            </div>

            {/* Revisions List */}
            <div className="dgw-revisions-items">
                {filteredRevisions.length > 0 ? (
                    filteredRevisions.map((revision) => (
                        <RevisionItem
                            key={revision.id}
                            revision={revision}
                            onApprove={approveRevision}
                            onReject={rejectRevision}
                            onView={handleViewRevision}
                        />
                    ))
                ) : (
                    <div className="dgw-no-revisions">
                        <Text>
                            {searchTerm || postTypeFilter || authorFilter
                                ? __('No revisions match your filters.', 'dgwltd-pending-revisions')
                                : __('No pending revisions found.', 'dgwltd-pending-revisions')
                            }
                        </Text>
                    </div>
                )}
            </div>

            {/* Load More */}
            {filteredRevisions.length > 0 && (
                <div className="dgw-load-more">
                    <Button variant="tertiary">
                        {__('Load More', 'dgwltd-pending-revisions')}
                    </Button>
                </div>
            )}
        </div>
    );
};