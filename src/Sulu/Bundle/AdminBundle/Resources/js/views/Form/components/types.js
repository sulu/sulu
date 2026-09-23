// @flow

export type WorkflowTransitionRequestStatus = 'pending' | 'approved' | 'cancelled' | 'published' | 'unknown';

/** A validator's decision starts pending and is answered later, possibly by a worker. */
export type CheckStatus = 'pending' | 'approved' | 'rejected';

/** A person's decision exists only once they decided, so it has no pending case. */
export type ApprovalStatus = 'approved' | 'rejected';

/** Either a translation key with its parameters, or text no fixed key could express. */
export type DecisionMessage = {|
    key: ?string,
    parameters: {[string]: string | number},
    text: ?string,
|};

export type User = {|
    fullName: string,
    id: number | string,
|};

export type WorkflowTransitionRequestCheck = {|
    decidedAt: ?string,
    id: string,
    messages: Array<DecisionMessage>,
    required: boolean,
    status: CheckStatus,
    validatorKey: string,
|};

export type WorkflowTransitionRequestApproval = {|
    decidedAt: ?string,
    id: string,
    messages: Array<DecisionMessage>,
    reviewer: ?User,
    status: ApprovalStatus,
|};

export type ApprovalProgress = {|
    approved: number,
    rejected: number,
    required: number,
|};

/**
 * What the server lets the current user do with this request. Content without object security, an
 * article or a snippet, carries no permissions of its own, so the answer travels with the request.
 */
export type WorkflowTransitionRequestPermissions = {|
    cancel: boolean,
    publish: boolean,
    retry: boolean,
    review: boolean,
|};

export type WorkflowTransitionRequestData = {|
    approvalProgress: ApprovalProgress,
    approvals: Array<WorkflowTransitionRequestApproval>,
    checks: Array<WorkflowTransitionRequestCheck>,
    createdBy: ?User,
    id: string,
    locale: string,
    permissions: WorkflowTransitionRequestPermissions,
    requestedAt: string,
    resourceId: string,
    resourceKey: string,
    status: WorkflowTransitionRequestStatus,
|};

export type PreValidationResult = {|
    key: string,
    messages: Array<DecisionMessage>,
    passed: boolean,
|};
