// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import jexl from 'jexl';
import Form from '../../components/Form';
import conditionDataProviderRegistry from './registries/conditionDataProviderRegistry';
import FormInspector from './FormInspector';
import Field from './Field';
import type {SchemaEntry} from './types';

type Props = {|
    children: false | ChildrenArray<?Element<typeof Field | typeof Section>>,
    data: Object,
    formInspector: FormInspector,
    name: string,
    schema: SchemaEntry,
|};

@observer
class Section extends React.Component<Props> {
    @computed get conditionData() {
        const {data, formInspector} = this.props;
        const {locale, metadataOptions, options} = formInspector;

        return conditionDataProviderRegistry.getAll().reduce(
            function(data, conditionDataProvider) {
                return {...data, ...conditionDataProvider(data, options, metadataOptions)};
            },
            {...data, __locale: locale}
        );
    }

    @computed get visible() {
        const {schema} = this.props;

        if (!schema.visibleCondition) {
            return true;
        }

        return jexl.evalSync(schema.visibleCondition, this.conditionData);
    }

    render() {
        if (!this.visible) {
            return null;
        }

        const {children, name, schema} = this.props;
        const {colSpan, label} = schema;

        return (
            <Form.Section colSpan={colSpan} key={name} label={label}>
                {children}
            </Form.Section>
        );
    }
}

export default Section;
