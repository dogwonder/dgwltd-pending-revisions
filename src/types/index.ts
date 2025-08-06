/**
 * TypeScript Type Definitions
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

/**
 * Editing modes for posts
 */
export type EditingMode = 'off' | 'open' | 'pending' | 'locked';

/**
 * Revision status
 */
export type RevisionStatus = 'pending' | 'accepted' | 'rejected';

/**
 * User capabilities
 */
export interface UserCapabilities {
    accept_revisions: boolean;
    manage_pending_revisions: boolean;
    edit_posts: boolean;
}

/**
 * Revision data structure
 */
export interface Revision {
    id: number;
    date: string;
    date_gmt: string;
    modified: string;
    modified_gmt: string;
    parent: number;
    author: number;
    author_name: string;
    title: {
        rendered: string;
    };
    content: {
        rendered: string;
    };
    excerpt: {
        rendered: string;
    };
    status: RevisionStatus;
    is_accepted: boolean;
    is_pending: boolean;
}

/**
 * Post data structure
 */
export interface PostData {
    id?: number;
    type?: string;
    status?: string;
    title: string;
    content: string;
    excerpt?: string;
    editing_mode?: EditingMode;
    accepted_revision_id?: number | false;
    pending_count?: number;
}

/**
 * Plugin data passed from PHP
 */
export interface PluginData {
    postId: number;
    postType: string;
    postStatus: string;
    editingMode: EditingMode;
    acceptedRevisionId: number | false;
    canAcceptRevisions: boolean;
    apiUrl: string;
    nonce: string;
    currentUser: number;
}

/**
 * API response structure
 */
export interface ApiResponse<T = any> {
    success: boolean;
    message?: string;
    data?: T;
}

/**
 * API error structure
 */
export interface ApiError {
    code: string;
    message: string;
    status: number;
}

/**
 * Revision statistics
 */
export interface RevisionStats {
    total_revisions: number;
    pending_revisions: number;
    approved_today: number;
    rejected_today: number;
}

/**
 * Notification data
 */
export interface NotificationData {
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    dismissible?: boolean;
}

/**
 * Component props for revision list item
 */
export interface RevisionListItemProps {
    revision: Revision;
    isAccepted: boolean;
    canAcceptRevisions: boolean;
    onApprove: (revisionId: number) => void;
    onReject: (revisionId: number) => void;
    onView: (revisionId: number) => void;
}

/**
 * Component props for editing mode selector
 */
export interface EditingModeSelectorProps {
    currentMode: EditingMode;
    canEdit: boolean;
    onChange: (mode: EditingMode) => void;
}

/**
 * Component props for revision comparison
 */
export interface RevisionComparisonProps {
    fromRevision: Revision;
    toRevision: Revision;
    onClose: () => void;
}

/**
 * WordPress global types augmentation
 */
declare global {
    interface Window {
        dgwPendingRevisionsEditorData: PluginData;
        wp: {
            apiFetch: (options: any) => Promise<any>;
            i18n: {
                __: (text: string, domain?: string) => string;
                _n: (single: string, plural: string, number: number, domain?: string) => string;
                sprintf: (format: string, ...args: any[]) => string;
            };
            element: {
                useState: typeof import('react').useState;
                useEffect: typeof import('react').useEffect;
                useCallback: typeof import('react').useCallback;
                useMemo: typeof import('react').useMemo;
                createElement: typeof import('react').createElement;
                Fragment: typeof import('react').Fragment;
            };
            components: any;
            data: any;
            compose: any;
            coreData: any;
        };
    }
}