/**
 * Editing Mode Selector Component
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

import { SelectControl, Flex, FlexItem, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { lock, unlock, check } from '@wordpress/icons';
import type { EditingModeSelectorProps } from '../types';

/**
 * Editing mode selector component
 */
export const EditingModeSelector = ({
    currentMode,
    canEdit,
    isUpdating = false,
    onChange,
}: EditingModeSelectorProps & { isUpdating?: boolean }) => {
    
    /**
     * Get editing mode options
     */
    const getModeOptions = () => [
        {
            label: __('Open - All changes published immediately', 'dgwltd-pending-revisions'),
            value: 'open',
        },
        {
            label: __('Requires Approval - Changes need approval', 'dgwltd-pending-revisions'),
            value: 'pending',
        },
    ];

    /**
     * Get mode icon
     */
    const getModeIcon = (mode: string) => {
        switch (mode) {
            case 'open':
                return unlock;
            case 'pending':
                return check;
            default:
                return unlock;
        }
    };

    /**
     * Get mode description
     */
    const getModeDescription = (mode: string): string => {
        switch (mode) {
            case 'open':
                return __('Anyone with edit permissions can publish changes immediately.', 'dgwltd-pending-revisions');
            case 'pending':
                return __('Changes must be approved by an editor before being published. Current published version is locked until approval.', 'dgwltd-pending-revisions');
            default:
                return '';
        }
    };

    /**
     * Handle mode change
     */
    const handleModeChange = (newMode: string) => {
        if (canEdit && newMode !== currentMode) {
            onChange(newMode as typeof currentMode);
        }
    };

    return (
        <div className="dgw-editing-mode-selector">
            <Flex direction="column" gap={3}>
                {/* Current mode display */}
                <FlexItem>
                    <Flex align="center" gap={2}>
                        <FlexItem>
                            <Icon icon={getModeIcon(currentMode)} />
                        </FlexItem>
                        <FlexItem>
                            <strong>
                                {__('Current Mode:', 'dgwltd-pending-revisions')} 
                                {currentMode.charAt(0).toUpperCase() + currentMode.slice(1)}
                            </strong>
                        </FlexItem>
                    </Flex>
                </FlexItem>

                {/* Mode description */}
                <FlexItem>
                    <p className="description">
                        {getModeDescription(currentMode)}
                    </p>
                </FlexItem>

                {/* Mode selector (if user can edit) */}
                {canEdit && (
                    <FlexItem>
                        <SelectControl
                            label={__('Change Editing Mode', 'dgwltd-pending-revisions')}
                            value={currentMode}
                            options={getModeOptions()}
                            onChange={handleModeChange}
                            disabled={isUpdating}
                            help={
                                isUpdating 
                                    ? __('Updating...', 'dgwltd-pending-revisions')
                                    : __('Select a different editing mode for this post.', 'dgwltd-pending-revisions')
                            }
                        />
                    </FlexItem>
                )}

                {/* Permission notice for non-editors */}
                {!canEdit && (
                    <FlexItem>
                        <p className="dgw-permission-notice">
                            {__('You do not have permission to change the editing mode for this post.', 'dgwltd-pending-revisions')}
                        </p>
                    </FlexItem>
                )}
            </Flex>
        </div>
    );
};