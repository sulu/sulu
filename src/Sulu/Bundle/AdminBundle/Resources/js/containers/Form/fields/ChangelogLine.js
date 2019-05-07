// @flow
import React, {Fragment} from 'react';
import {action, autorun, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Loader from '../../../components/Loader';
import ResourceRequester from '../../../services/ResourceRequester';
import {translate} from '../../../utils/Translator';
import type {FieldTypeProps} from '../../../types';

export default @observer class ChangelogLine extends React.Component<FieldTypeProps<typeof undefined>> {
    @observable changer: ?Object;
    @observable creator: ?Object;
    @observable changerLoaded: boolean = false;
    @observable creatorLoaded: boolean = false;
    changerDisposer: () => void;
    creatorDisposer: () => void;

    componentDidMount() {
        this.changerDisposer = autorun(this.loadChanger);
        this.creatorDisposer = autorun(this.loadCreator);
    }

    componentWillUnmount() {
        this.changerDisposer();
        this.creatorDisposer();
    }

    loadChanger = () => {
        if (typeof this.changerId !== 'number') {
            this.setChanger(undefined);
            return;
        }

        ResourceRequester.get('users', {id: this.changerId}).then(action((changer) => {
            this.setChanger(changer);
        }));
    };

    loadCreator = () => {
        if (typeof this.creatorId !== 'number') {
            this.setCreator(undefined);
            return;
        }

        ResourceRequester.get('users', {id: this.creatorId}).then(action((creator) => {
            this.setCreator(creator);
        }));
    };

    @action setChanger(changer: ?Object) {
        this.changer = changer;
        this.changerLoaded = true;
    }

    @action setCreator(creator: ?Object) {
        this.creator = creator;
        this.creatorLoaded = true;
    }

    @computed get changerId() {
        return this.props.formInspector.getValueByPath('/changer');
    }

    @computed get creatorId() {
        return this.props.formInspector.getValueByPath('/creator');
    }

    @computed get changerFullName() {
        return this.changer ? this.changer.fullName : undefined;
    }

    @computed get changed() {
        const {formInspector} = this.props;
        const changed = formInspector.getValueByPath('/changed');
        if (typeof changed !== 'string') {
            return;
        }

        return (new Date(changed)).toLocaleString();
    }

    @computed get creatorFullName() {
        return this.creator ? this.creator.fullName : undefined;
    }

    @computed get created() {
        const {formInspector} = this.props;
        const created = formInspector.getValueByPath('/created');
        if (typeof created !== 'string') {
            return;
        }

        return (new Date(created)).toLocaleString();
    }

    render() {
        if (!this.changerLoaded || !this.creatorLoaded) {
            return (
                <Loader />
            );
        }

        return (
            <Fragment>
                <p>
                    {translate(
                        'sulu_admin.changelog_line_changer',
                        {changer: String(this.changerFullName), changed: this.changed}
                    )}
                </p>
                <p>
                    {translate(
                        'sulu_admin.changelog_line_creator',
                        {creator: String(this.creatorFullName), created: this.created}
                    )}
                </p>
            </Fragment>
        );
    }
}
