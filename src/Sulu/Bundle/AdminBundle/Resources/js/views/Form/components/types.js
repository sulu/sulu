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

export type WorkflowTransitionRequestData = {|
    approvalProgress: ApprovalProgress,
    approvals: Array<WorkflowTransitionRequestApproval>,
    checks: Array<WorkflowTransitionRequestCheck>,
    createdBy: ?User,
    id: string,
    locale: string,
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
