// @flow
import {action, autorun, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import log from 'loglevel';
import Loader from '../../components/Loader';
import Renderer from './Renderer';
import FormStore from './stores/FormStore';
import FormInspector from './FormInspector';
import GhostDialog from './GhostDialog';

type Props = {
    store: FormStore,
    onSubmit: (action: ?string) => ?Promise<Object>,
};

@observer
export default class Form extends React.Component<Props> {
    @observable showAllErrors = false;
    @observable displayGhostDialog = false;
    displayGhostDialogDisposer: () => void;

    constructor(props: Props) {
        super(props);

        this.displayGhostDialogDisposer = autorun(() => {
            const {store} = this.props;
            const {
                data: {
                    concreteLanguages,
                },
                loading,
                locale,
            } = store;

            if (loading) {
                this.hideGhostDialog();
                return;
            }

            if (concreteLanguages && locale && !concreteLanguages.includes(locale.get())) {
                this.showGhostDialog();
            }
        });
    }

    componentWillUnmount() {
        this.displayGhostDialogDisposer();
    }

    @computed get formInspector(): FormInspector {
        return new FormInspector(this.props.store);
    }

    /** @public */
    @action submit = (action: ?string) => {
        this.showAllErrors = true;
        return this.props.onSubmit(action);
    };

    handleChange = (name: string, value: mixed) => {
        this.props.store.change(name, value);
    };

    @action showGhostDialog() {
        this.displayGhostDialog = true;
    }

    @action hideGhostDialog() {
        this.displayGhostDialog = false;
    }

    @action handleGhostDialogCancel = () => {
        this.hideGhostDialog();
    };

    @action handleGhostDialogConfirm = (locale: string) => {
        this.props.store.copyFromLocale(locale);
        this.hideGhostDialog();
    };

    handleFieldFinish = (dataPath: string, schemaPath: string) => {
        log.debug(
            'Finished editing field with dataPath "' + dataPath + '" and schemaPath "' + schemaPath + '"',
            toJS(this.formInspector.getValueByPath(dataPath))
        );
        const {store} = this.props;

        store.validate();
        this.formInspector.finishField(dataPath, schemaPath);
    };

    render() {
        const {store} = this.props;
        const {
            data: {
                concreteLanguages,
            },
        } = store;

        return store.loading
            ? <Loader />
            : (
                <Fragment>
                    {store.id && concreteLanguages &&
                        <GhostDialog
                            locales={concreteLanguages}
                            onCancel={this.handleGhostDialogCancel}
                            onConfirm={this.handleGhostDialogConfirm}
                            open={this.displayGhostDialog}
                        />
                    }
                    <Renderer
                        data={store.data}
                        dataPath=""
                        errors={store.errors}
                        formInspector={this.formInspector}
                        onChange={this.handleChange}
                        onFieldFinish={this.handleFieldFinish}
                        schema={store.schema}
                        schemaPath=""
                        showAllErrors={this.showAllErrors}
                    />
                </Fragment>
            );
    }
}
