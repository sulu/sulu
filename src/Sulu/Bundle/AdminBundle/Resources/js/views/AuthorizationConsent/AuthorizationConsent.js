// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import symfonyRouting from 'fos-jsrouting/router';
import Button from '../../components/Button';
import Loader from '../../components/Loader';
import Snackbar from '../../components/Snackbar';
import Requester from '../../services/Requester';
import RequestPromise from '../../services/Requester/RequestPromise';
import {translate} from '../../utils/Translator';
import authorizationConsentStyles from './authorizationConsent.scss';
import type {ViewProps} from '../../containers/ViewRenderer';

type Scope = {
    id: string,
    label: string,
};

type ConsentDetails = {
    clientName: string,
    redirectUri?: string,
    scopes: Array<Scope>,
};

function parseConsentDetails(response: Object): ConsentDetails {
    const {clientName, redirectUri, scopes} = response;

    if (typeof clientName !== 'string') {
        throw new Error('The authorization consent response must contain a "clientName" string!');
    }

    if (!Array.isArray(scopes)) {
        throw new Error('The authorization consent response must contain a "scopes" array!');
    }

    return {
        clientName,
        redirectUri: typeof redirectUri === 'string' ? redirectUri : undefined,
        scopes,
    };
}

@observer
class AuthorizationConsent extends React.Component<ViewProps> {
    @observable consentDetails: ?ConsentDetails;
    @observable error: boolean = false;
    @observable loading: boolean = true;
    @observable savingDecision: ?boolean;

    detailsRequest: ?RequestPromise<Object>;
    decisionRequest: ?RequestPromise<Object>;

    componentDidMount() {
        this.detailsRequest = Requester.get(this.detailsUrl);
        this.detailsRequest
            .then(action((response) => {
                this.detailsRequest = undefined;
                this.consentDetails = parseConsentDetails(response);
                this.error = false;
                this.loading = false;
            }))
            .catch(action((error) => {
                if (error && error.name === 'AbortError') {
                    return;
                }

                this.detailsRequest = undefined;
                this.error = true;
                this.loading = false;
            }));
    }

    componentWillUnmount() {
        if (this.detailsRequest) {
            this.detailsRequest.abort();
        }

        if (this.decisionRequest) {
            this.decisionRequest.abort();
        }
    }

    get requestId(): string {
        const {requestId} = this.props.router.attributes;
        if (typeof requestId !== 'string') {
            throw new Error('The "requestId" router attribute must be a string!');
        }

        return requestId;
    }

    get detailsUrl(): string {
        return this.resolveRouteUrl('detailsRoute');
    }

    get decisionUrl(): string {
        return this.resolveRouteUrl('decisionRoute');
    }

    resolveRouteUrl(optionName: string): string {
        const routeName = this.props.route.options[optionName];
        if (typeof routeName !== 'string') {
            throw new Error('The "' + optionName + '" route option must be a string!');
        }

        return symfonyRouting.generate(routeName, {requestId: this.requestId});
    }

    handleApprove = () => {
        this.submitDecision(true);
    };

    handleDeny = () => {
        this.submitDecision(false);
    };

    submitDecision = action((approved: boolean) => {
        this.error = false;
        this.savingDecision = approved;

        this.decisionRequest = Requester.post(this.decisionUrl, {approved});
        this.decisionRequest
            .then((response) => {
                this.decisionRequest = undefined;

                const {redirectUrl} = response;
                if (typeof redirectUrl !== 'string') {
                    throw new Error('The authorization consent response must contain a "redirectUrl" string!');
                }

                window.location.assign(redirectUrl);
            })
            .catch(action((error) => {
                if (error && error.name === 'AbortError') {
                    return;
                }

                this.decisionRequest = undefined;
                this.error = true;
                this.savingDecision = undefined;
            }));
    });

    render() {
        const {consentDetails, error, loading, savingDecision} = this;

        if (loading) {
            return (
                <div className={authorizationConsentStyles.loader}>
                    <Loader size={40} />
                </div>
            );
        }

        if (!consentDetails) {
            return (
                <div className={authorizationConsentStyles.authorizationConsent}>
                    <Snackbar
                        message={translate('sulu_admin.authorization_consent_error')}
                        type="error"
                    />
                </div>
            );
        }

        const {clientName, redirectUri, scopes} = consentDetails;

        return (
            <section className={authorizationConsentStyles.authorizationConsent}>
                <div className={authorizationConsentStyles.content}>
                    <h1>{translate('sulu_admin.authorization_consent_title')}</h1>
                    <p className={authorizationConsentStyles.description}>
                        {translate('sulu_admin.authorization_consent_description', {clientName})}
                    </p>

                    <dl className={authorizationConsentStyles.metadata}>
                        <dt>{translate('sulu_admin.authorization_consent_application')}</dt>
                        <dd>{clientName}</dd>
                        {redirectUri &&
                            <>
                                <dt>{translate('sulu_admin.authorization_consent_redirect_uri')}</dt>
                                <dd>{redirectUri}</dd>
                            </>
                        }
                    </dl>

                    {scopes.length > 0 &&
                        <div className={authorizationConsentStyles.scopes}>
                            <h2>{translate('sulu_admin.authorization_consent_scopes')}</h2>
                            <ul className={authorizationConsentStyles.scopeList}>
                                {scopes.map((scope) => (
                                    <li className={authorizationConsentStyles.scope} key={scope.id}>
                                        {scope.label}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    }

                    {error &&
                        <div className={authorizationConsentStyles.notification}>
                            <Snackbar
                                message={translate('sulu_admin.authorization_consent_decision_error')}
                                type="error"
                            />
                        </div>
                    }

                    <div className={authorizationConsentStyles.actions}>
                        <Button
                            disabled={savingDecision !== undefined}
                            loading={savingDecision === false}
                            onClick={this.handleDeny}
                            skin="secondary"
                        >
                            {translate('sulu_admin.authorization_consent_deny')}
                        </Button>
                        <Button
                            disabled={savingDecision !== undefined}
                            loading={savingDecision === true}
                            onClick={this.handleApprove}
                            skin="primary"
                        >
                            {translate('sulu_admin.authorization_consent_approve')}
                        </Button>
                    </div>
                </div>
            </section>
        );
    }
}

export default AuthorizationConsent;
