// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import Button from '../../../components/Button';
import Icon from '../../../components/Icon';
import workflowCheckCardStyles from './workflowCheckCard.scss';
import type {Node} from 'react';

/** `pending` is a check that has not answered yet, `waiting` an approval nobody has given yet. */
export type RowStatus = 'approved' | 'pending' | 'rejected' | 'waiting';

export type CheckRow = {|
    /** Label of an inline action, e.g. retrying a validator. Without it no button is rendered. */
    actionLabel?: ?string,
    actionValue?: ?string,
    caption: string,
    comment?: ?string,
    id: string,
    status: RowStatus,
    title: ?string,
|};

const STATUS_ICON: {[RowStatus]: string} = {
    approved: 'su-check',
    pending: 'su-circle-full',
    rejected: 'su-times',
    waiting: 'su-circle-full',
};

type RowProps = {|
    ...CheckRow,
    onAction?: ?(value: ?string) => void,
|};

@observer
class WorkflowCheckRow extends React.Component<RowProps> {
    @observable expanded: boolean = false;
    @observable clamped: boolean = false;

    commentRef: {current: null | HTMLElement} = React.createRef();

    componentDidMount() {
        this.measureComment();
    }

    componentDidUpdate() {
        this.measureComment();
    }

    /** Only the clamped state can be measured: expanded, the text always fits its own box. */
    @action measureComment = () => {
        const element = this.commentRef.current;
        if (!element || this.expanded) {
            return;
        }

        const clamped = element.scrollHeight - element.clientHeight > 1;
        if (clamped !== this.clamped) {
            this.clamped = clamped;
        }
    };

    @action handleCommentClick = () => {
        this.expanded = !this.expanded;
    };

    handleCommentKeyDown = (event: SyntheticKeyboardEvent<HTMLElement>) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        this.handleCommentClick();
    };

    renderComment(comment: string) {
        // Three lines are always readable, the rest costs a click: a folded-away comment is a
        // comment nobody reads.
        const expandable = this.clamped || this.expanded;
        const interactionProps = expandable
            ? {
                onClick: this.handleCommentClick,
                onKeyDown: this.handleCommentKeyDown,
                role: 'button',
                tabIndex: 0,
            }
            : {};

        return (
            <span
                className={classNames(
                    workflowCheckCardStyles.rowComment,
                    {[workflowCheckCardStyles.rowCommentExpandable]: expandable}
                )}
                {...interactionProps}
            >
                <span
                    className={classNames(
                        workflowCheckCardStyles.rowCommentText,
                        {[workflowCheckCardStyles.rowCommentClamped]: !this.expanded}
                    )}
                    ref={this.commentRef}
                >
                    {comment}
                </span>
            </span>
        );
    }

    render() {
        const {actionLabel, actionValue, caption, comment, onAction, status, title} = this.props;

        return (
            <li className={workflowCheckCardStyles.row}>
                <div className={workflowCheckCardStyles.rowMain}>
                    <span className={classNames(workflowCheckCardStyles.rowIcon, workflowCheckCardStyles[status])}>
                        <Icon className={workflowCheckCardStyles.rowGlyph} name={STATUS_ICON[status]} />
                    </span>
                    <span className={workflowCheckCardStyles.rowContent}>
                        <span className={workflowCheckCardStyles.rowHeading}>
                            {title && <span className={workflowCheckCardStyles.rowTitle}>{title}</span>}
                            <span className={workflowCheckCardStyles.rowCaption}>{caption}</span>
                        </span>
                        {comment && this.renderComment(comment)}
                    </span>
                    {actionLabel && onAction && (
                        <Button
                            className={workflowCheckCardStyles.rowRetry}
                            onClick={onAction}
                            skin="link"
                            value={actionValue}
                        >
                            {actionLabel}
                        </Button>
                    )}
                </div>
            </li>
        );
    }
}

type Props = {|
    emptyText?: string,
    headerCount?: Node,
    /** Second header line, e.g. when the first one names a person and this dates the request. */
    headerDetail?: Node,
    headerText: Node,
    onAction?: ?(value: ?string) => void,
    rows: Array<CheckRow>,
|};

/**
 * The list of checks a piece of content has to pass, shared by the review overlay and the
 * pre-validation overlay: reviewers and pre-validators read the same way, so they look the same.
 */
export default class WorkflowCheckCard extends React.Component<Props> {
    render() {
        const {emptyText, headerCount, headerDetail, headerText, onAction, rows} = this.props;

        return (
            <ul className={workflowCheckCardStyles.card}>
                <li className={workflowCheckCardStyles.header}>
                    <div className={workflowCheckCardStyles.headerText}>
                        <div>{headerText}</div>
                        {headerDetail && (
                            <div className={workflowCheckCardStyles.headerDate}>{headerDetail}</div>
                        )}
                    </div>
                    {headerCount && <div className={workflowCheckCardStyles.headerCount}>{headerCount}</div>}
                </li>
                {rows.length === 0 && emptyText
                    ? <li className={workflowCheckCardStyles.empty}>{emptyText}</li>
                    : rows.map(({id, ...row}) => (
                        <WorkflowCheckRow {...row} id={id} key={id} onAction={onAction} />
                    ))
                }
            </ul>
        );
    }
}
