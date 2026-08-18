// @flow
import React, {Fragment} from 'react';
import classNames from 'classnames';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import symfonyRouting from 'fos-jsrouting/router';
import Button from '../../components/Button';
import Loader from '../../components/Loader';
import {withToolbar} from '../../containers/Toolbar';
import Requester from '../../services/Requester';
import {translate} from '../../utils/Translator';
import {getSubscriptionConfig} from './subscriptionConfig';
import subscriptionStyles from './subscription.scss';
import type {ViewProps} from '../../containers/ViewRenderer';

type Plan = {
    bundle: ?string,
    payAsYouGo: boolean,
    type: string,
};

type SubscriptionData = {
    credits: {
        hardLimit: number,
        included: number,
        remainingIncluded: number,
        remainingUntilHardLimit: number,
        remainingUntilSoftLimit: ?number,
        softLimit: ?number,
        used: number,
    },
    nextSubscription: ?{...Plan, startedAt: string},
    subscription: {
        ...Plan,
        isActive: boolean,
        isTrial: boolean,
        scheduledCancellationAt: ?string,
        startedAt: string,
    },
};

function capitalize(value: string): string {
    return value
        .split(/[_\s]+/)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

const DATE_FORMAT_OPTIONS = {day: '2-digit', month: '2-digit', year: 'numeric'};

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, DATE_FORMAT_OPTIONS);
}

function formatFirstOfNextMonth(): string {
    const now = new Date();

    return new Date(now.getFullYear(), now.getMonth() + 1, 1).toLocaleDateString(undefined, DATE_FORMAT_OPTIONS);
}

function formatPlan(plan: Plan): string {
    const typeLabel = plan.type === 'base'
        ? translate('sulu_ai_platform.subscription_type_base')
        : capitalize(plan.type);

    const parts = [typeLabel];

    if (plan.bundle) {
        parts.push(capitalize(plan.bundle));
    }

    if (plan.payAsYouGo) {
        parts.push(translate('sulu_ai_platform.subscription_pay_as_you_go'));
    }

    return parts.join(' + ');
}

@observer
class Subscription extends React.Component<ViewProps> {
    @observable data: ?SubscriptionData;
    @observable error: boolean = false;
    @observable loading: boolean = true;

    componentDidMount() {
        this.load();
    }

    @action handleTryAgainClick = () => {
        this.load();
    };

    @action load = () => {
        const config = getSubscriptionConfig();

        if (!config) {
            this.error = true;
            this.loading = false;

            return;
        }

        this.loading = true;
        this.error = false;

        Requester.get(symfonyRouting.generate(config.route))
            .then(action((data) => {
                this.data = data;
                this.loading = false;
            }))
            .catch(action(() => {
                this.error = true;
                this.loading = false;
            }));
    };

    @computed get status(): ?{date: ?string, label: string, skin: string} {
        const subscription = this.data?.subscription;

        if (!subscription) {
            return undefined;
        }

        if (!subscription.isActive) {
            return {
                date: subscription.scheduledCancellationAt,
                label: translate('sulu_ai_platform.subscription_status_canceled'),
                skin: 'canceled',
            };
        }

        if (subscription.scheduledCancellationAt) {
            return {
                date: subscription.scheduledCancellationAt,
                label: translate('sulu_ai_platform.subscription_status_will_be_canceled'),
                skin: 'willBeCanceled',
            };
        }

        return {
            date: undefined,
            label: translate('sulu_ai_platform.subscription_status_active'),
            skin: 'active',
        };
    }

    renderSubscriptionCard() {
        const data = this.data;
        const status = this.status;

        if (!data || !status) {
            return null;
        }

        const {nextSubscription, subscription} = data;

        return (
            <section className={subscriptionStyles.card}>
                <div className={subscriptionStyles.cardHeader}>
                    <h2>{translate('sulu_ai_platform.subscription_your_subscription')}</h2>
                </div>
                <div className={subscriptionStyles.facts}>
                    <div className={subscriptionStyles.fact}>
                        <span className={subscriptionStyles.factLabel}>
                            {translate('sulu_ai_platform.subscription_plan')}
                        </span>
                        <span className={subscriptionStyles.factValue}>
                            {formatPlan(subscription)}
                            {subscription.isTrial &&
                                ' (' + translate('sulu_ai_platform.subscription_trial') + ')'
                            }
                        </span>
                    </div>
                    <div className={subscriptionStyles.fact}>
                        <span className={subscriptionStyles.factLabel}>
                            {translate('sulu_ai_platform.subscription_status')}
                        </span>
                        <span className={classNames(subscriptionStyles.status, subscriptionStyles[status.skin])}>
                            {status.label}
                            {status.date && ' · ' + formatDate(status.date)}
                        </span>
                    </div>
                    <div className={subscriptionStyles.fact}>
                        <span className={subscriptionStyles.factLabel}>
                            {translate('sulu_ai_platform.subscription_started_on')}
                        </span>
                        <span className={subscriptionStyles.factValue}>
                            {formatDate(subscription.startedAt)}
                        </span>
                    </div>
                </div>
                {nextSubscription &&
                    <p className={subscriptionStyles.note}>
                        {translate('sulu_ai_platform.subscription_switches_to', {
                            date: formatDate(nextSubscription.startedAt),
                            plan: formatPlan(nextSubscription),
                        })}
                    </p>
                }
            </section>
        );
    }

    renderCreditsCard() {
        const data = this.data;

        if (!data) {
            return null;
        }

        const {credits, subscription} = data;
        const usedPercentage = credits.included > 0
            ? Math.min(100, (credits.used / credits.included) * 100)
            : 100;

        return (
            <section className={subscriptionStyles.card}>
                <div className={subscriptionStyles.cardHeader}>
                    <h2>{translate('sulu_ai_platform.subscription_credits_title')}</h2>
                </div>
                <div className={subscriptionStyles.creditsLeft}>
                    <span className={subscriptionStyles.creditsLeftNumber}>
                        {credits.remainingIncluded.toLocaleString()}
                    </span>
                    <span className={subscriptionStyles.creditsLeftText}>
                        {translate('sulu_ai_platform.subscription_credits_left', {
                            included: credits.included.toLocaleString(),
                        })}
                    </span>
                </div>
                <div className={subscriptionStyles.progressBar}>
                    <div className={subscriptionStyles.progressBarFill} style={{width: usedPercentage + '%'}} />
                </div>
                <div className={subscriptionStyles.progressLegend}>
                    <span>
                        {translate('sulu_ai_platform.subscription_credits_used', {
                            used: credits.used.toLocaleString(),
                        })}
                    </span>
                    {subscription.isActive && !subscription.scheduledCancellationAt &&
                        <span>
                            {translate('sulu_ai_platform.subscription_renews_on') + ' ' + formatFirstOfNextMonth()}
                        </span>
                    }
                </div>
                <p className={subscriptionStyles.note}>
                    {subscription.payAsYouGo
                        ? translate('sulu_ai_platform.subscription_credits_note_pay_as_you_go')
                        : translate('sulu_ai_platform.subscription_credits_note_included_only')
                    }
                </p>
            </section>
        );
    }

    renderContactBox() {
        const contactEmail = getSubscriptionConfig()?.contactEmail;

        if (!contactEmail) {
            return null;
        }

        return (
            <section className={subscriptionStyles.contact}>
                <p>{translate('sulu_ai_platform.subscription_contact_text')}</p>
                <a className={subscriptionStyles.contactLink} href={'mailto:' + contactEmail}>
                    {contactEmail}
                </a>
            </section>
        );
    }

    renderError() {
        return (
            <div className={subscriptionStyles.error}>
                <p>{translate('sulu_ai_platform.subscription_unavailable')}</p>
                <Button onClick={this.handleTryAgainClick} skin="secondary">
                    {translate('sulu_admin.try_again')}
                </Button>
            </div>
        );
    }

    render() {
        return (
            <div className={subscriptionStyles.subscription}>
                <h1 className={subscriptionStyles.title}>
                    {translate('sulu_ai_platform.subscription')}
                </h1>
                <p className={subscriptionStyles.description}>
                    {translate('sulu_ai_platform.subscription_description')}
                </p>
                {this.loading && <Loader />}
                {!this.loading && this.error && this.renderError()}
                {!this.loading && !this.error &&
                    <Fragment>
                        {this.renderSubscriptionCard()}
                        {this.renderCreditsCard()}
                    </Fragment>
                }
                {!this.loading && this.renderContactBox()}
            </div>
        );
    }
}

export default withToolbar(Subscription, function() {
    return {};
});
