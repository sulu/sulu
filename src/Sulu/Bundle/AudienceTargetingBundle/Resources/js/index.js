// @flow
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import {initializer} from 'sulu-admin-bundle/services';
import TargetGroupRules from './containers/Form/fields/TargetGroupRules';
import ruleRegistry from './containers/TargetGroupRules/registries/RuleRegistry';
import ruleTypeRegistry from './containers/TargetGroupRules/registries/RuleTypeRegistry';
import KeyValue from './containers/TargetGroupRules/ruleTypes/KeyValue';
import Input from './containers/TargetGroupRules/ruleTypes/Input';
import SingleSelect from './containers/TargetGroupRules/ruleTypes/SingleSelect';
import SingleSelection from './containers/TargetGroupRules/ruleTypes/SingleSelection';

initializer.addUpdateConfigHook('sulu_audience_targeting', (config: Object, initialized: boolean) => {
    if (initialized || !config) {
        return;
    }

    ruleRegistry.setRules(config.targetGroupRules);

    fieldRegistry.add('target_group_rules', TargetGroupRules);

    ruleTypeRegistry.add('key_value', KeyValue);
    ruleTypeRegistry.add('input', Input);
    ruleTypeRegistry.add('single_select', SingleSelect);
    ruleTypeRegistry.add('single_selection', SingleSelection);
});
