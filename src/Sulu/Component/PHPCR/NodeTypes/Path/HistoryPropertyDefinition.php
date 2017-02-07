<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\NodeTypes\Path;

use PHPCR\NodeType\PropertyDefinitionInterface;
use PHPCR\PropertyType;
use PHPCR\Version\OnParentVersionAction;

class HistoryPropertyDefinition implements PropertyDefinitionInterface
{
    /**
     * Gets the node type that contains the declaration of this ItemDefinition.
     *
     * In implementations that support node type registration an ItemDefinition
     * object may be acquired (in the form of a NodeDefinitionTemplate or
     * PropertyDefinitionTemplate) that is not attached to a live NodeType. In
     * such cases this method returns null.
     *
     * @return \PHPCR\NodeType\NodeTypeInterface A NodeType object
     *
     * @api
     */
    public function getDeclaringNodeType()
    {
        return new PathNodeType();
    }

    /**
     * Gets the name of the child item.
     *
     * If "*", this ItemDefinition defines a residual set of child items.
     * That is, it defines the characteristics of all those child items with names
     * apart from the names explicitly used in other child item definitions.
     *
     * In implementations that support node type registration, if this
     * ItemDefinition object is actually a newly-created empty
     * PropertyDefinitionTemplate or NodeDefinitionTemplate, then this method
     * will return null.
     *
     * @return string A string denoting the name or "*"
     *
     * @api
     */
    public function getName()
    {
        return 'sulu:history';
    }

    /**
     * Reports whether the item is to be automatically created when its parent
     * node is created.
     *
     * If true, then this ItemDefinition will necessarily not
     * be a residual set definition but will specify an actual item name (in
     * other words getName() will not return "*").
     *
     * An autocreated non-protected item must be created immediately when
     * its parent node is created in the transient session space. Creation of
     * autocreated non-protected items is never delayed until save.
     *
     * An autocreated protected item should be created immediately when its
     * parent node is created in the transient session space. Creation of
     * autocreated protected items should not be delayed until save, though
     * doing so does not violate JCR compliance.
     *
     * In implementations that support node type registration, if this
     * ItemDefinition object is actually a newly-created empty
     * PropertyDefinitionTemplate or NodeDefinitionTemplate, then this method
     * will return false.
     *
     * @return bool True, if the item is automatically created when its
     *              parent node is created, else false
     *
     * @api
     */
    public function isAutoCreated()
    {
        return true;
    }

    /**
     * Reports whether the item is mandatory.
     *
     * A mandatory item is one that, if its parent node exists, must also exist.
     * This means that a mandatory single-value property must have a value (since
     * there is no such thing a null value). In the case of multi-value properties
     * this means that the property must exist, though it can have zero or more
     * values.
     *
     * An attempt to save a node that has a mandatory child item without first
     * creating that child item will throw a ConstraintViolationException on save.
     *
     * In implementations that support node type registration, if this
     * ItemDefinition object is actually a newly-created empty
     * PropertyDefinitionTemplate or NodeDefinitionTemplate, then this method
     * will return false.
     *
     * An item definition cannot be both residual and mandatory.
     *
     * @return bool True, if the item is mandatory, else false
     *
     * @api
     */
    public function isMandatory()
    {
        return true;
    }

    /**
     * Gets the OnParentVersion status of the child item.
     *
     * This governs what occurs (in implementations that support versioning)
     * when the parent node of this item is checked-in. One of:
     *
     * - OnParentVersionAction::COPY
     * - OnParentVersionAction::VERSION
     * - OnParentVersionAction::IGNORE
     * - OnParentVersionAction::INITIALIZE
     * - OnParentVersionAction::COMPUTE
     * - OnParentVersionAction::ABORT
     *
     * In implementations that support node type registration, if this
     * ItemDefinition object is actually a newly-created empty
     * PropertyDefinitionTemplateInterface or NodeDefinitionTemplateInterface,
     * then this method will return OnParentVersionAction::COPY.
     *
     * @return int An int constant member of OnParentVersionAction
     *
     * @api
     */
    public function getOnParentVersion()
    {
        return OnParentVersionAction::COPY;
    }

    /**
     * Reports whether the child item is protected.
     *
     * In level 2 implementations, a protected item is one that cannot be
     * removed (except by removing its parent) or modified through the the
     * standard write methods of this API (that is, ItemInterface::remove(),
     * NodeInterface::addNode(), NodeInterface::setProperty() and
     * PropertyInterface::setValue()).
     *
     * A protected node may be removed or modified (in a level 2
     * implementation), however, through some mechanism not defined by this
     * specification or as a side-effect of operations other than the standard
     * write methods of the API.
     *
     * In implementations that support node type registration, if this
     * ItemDefinition object is actually a newly-created empty
     * PropertyDefinitionTemplateInterface or NodeDefinitionTemplateInterface,
     * then this method will return false.
     *
     * @return bool True, if the child item is protected, else false
     *
     * @api
     */
    public function isProtected()
    {
        return false;
    }

    /**
     * Gets the required type of the property.
     *
     * Possible property types of:
     *
     * - PropertyType::STRING
     * - PropertyType::DATE
     * - PropertyType::BINARY
     * - PropertyType::DOUBLE
     * - PropertyType::DECIMAL
     * - PropertyType::LONG
     * - PropertyType::BOOLEAN
     * - PropertyType::NAME
     * - PropertyType::PATH
     * - PropertyType::URI
     * - PropertyType::REFERENCE
     * - PropertyType::WEAKREFERENCE
     * - PropertyType::UNDEFINED
     *
     * PropertyType::UNDEFINED is returned if this property may be of any type.
     *
     * In implementations that support node type registration, if this
     * PropertyDefinition object is actually a newly-created empty
     * PropertyDefinitionTemplate, then this method will return PropertyType::STRING.
     *
     * @return int An integer constant member of PropertyType
     *
     * @api
     */
    public function getRequiredType()
    {
        return PropertyType::BOOLEAN;
    }

    /**
     * Gets the array of constraint strings.
     *
     * Each string in the array specifies a constraint on the value of the property.
     * The constraints are OR-ed together, meaning that in order to be valid, the
     * value must meet at least one of the constraints. For example, a constraint
     * array of ["constraint1", "constraint2", "constraint3"] has the interpretation:
     * "the value of this property must meet at least one of constraint1, constraint2
     * or constraint3".
     *
     * Reporting of value constraints is optional. An implementation may return
     * null, indicating that value constraint information is unavailable (though
     * a constraint may still exist).
     *
     * Returning an empty array, on the other hand, indicates that value constraint
     * information is available and that no constraints are placed on this value.
     *
     * In the case of multi-value properties, the constraint string array returned
     * applies to all the values of the property.
     *
     * The constraint strings themselves having differing formats and
     * interpretations depending on the type of the property in question. The
     * following describes the value constraint syntax for each property type:
     *
     * <b>STRING</b> and <b>URI</b>: The constraint string is a regular
     * expression pattern. For example the regular expression ".*" means "any
     * string, including the empty string". Whereas a simple literal string
     * (without any RE-specific meta-characters) like "banana" matches only the
     * string "banana".
     *
     * <b>PATH</b>: The constraint string is a JCR path with an optional "*"
     * character after the last "/" character. For example, possible constraint
     * strings for a property of type PATH include:
     *
     * - "/myapp:products/myapp:televisions"
     * - "/myapp:products/myapp:televisions/"
     * - "/myapp:products/*"
     * - "myapp:products/myapp:televisions"
     * - "../myapp:televisions"
     * - "../myapp:televisions/*"
     *
     * The following principles apply:
     *
     * - The "*" means "matches descendants" not "matches any subsequent path".
     *   For example, /a/* does not match /a/../c. The constraint must match the
     *   normalized path.
     * - Relative path constraint only match relative path values and absolute
     *   path constraints only match absolute path values.
     * - A trailing "/" has no effect (hence, 1 and 2, above, are equivalent).
     * - The trailing "*" character means that the value of the PATH property is
     *   restricted to the indicated subgraph (in other words any additional
     *   relative path can replace the "*"). For example, 3, above would allow
     *   /myapp:products/myapp:radios, /myapp:products/myapp:microwaves/X900,
     *   and so forth.
     * - A constraint without a "*" means that the PATH property is restricted
     *   to that precise path. For example, 1, above, would allow only the
     *   value /myapp:products/myapp:televisions.
     * - The constraint can indicate either a relative path or an absolute path
     *   depending on whether it includes a leading "/" character. 1 and 4,
     *   above for example, are distinct.
     * - The string returned must reflect the namespace mapping in the current
     *   Session (i.e., the current state of the namespace registry overlaid
     *   with any session-specific mappings). Constraint strings for PATH
     *   properties should be stored in fully-qualified form (using the actual
     *   URI instead of the prefix) and then be converted to prefix form
     *   according to the current mapping upon the
     *   PropertyDefinitionInterface::getValueConstraints() call.
     *
     * <b>NAME</b>: The constraint string is a JCR name in prefix form. For example
     * "myapp:products". No wildcards or other pattern matching are supported.
     * As with PATH properties, the string returned must reflect the namespace
     * mapping in the current Session. Constraint strings for NAME properties
     * should be stored in fully-qualified form (using the actual URI instead of
     * the prefix) and then be converted to prefix form according to the current
     * mapping.
     *
     * <b>REFERENCE</b> and <b>WEAKREFERENCE</b>: The constraint string is a
     * JCR name in prefix form. This name is interpreted as a node type name
     * and the REFERENCE or WEAKREFERENCE property is restricted to referring
     * only to nodes that have at least the indicated node type. For example, a
     * constraint of "mytype:document" would indicate that the property in
     * question can only refer to nodes that have at least the node type
     * mytype:document (assuming this was the only constraint returned in the
     * array, recall that the array of constraints are to be ORed together). No
     * wildcards or other pattern matching are supported.
     *
     * As with PATH properties, the string returned must reflect the namespace
     * mapping in the current Session. Constraint strings for REFERENCE and
     * WEAKREFERENCE properties should be stored by the implementation in
     * fully-qualified form (using the actual URI instead of the prefix) and then
     * be converted to prefix form according to the current mapping.
     *
     * <b>BOOLEAN</b>: BOOLEAN properties will always report a value constraint
     * consisting of an empty array (meaning no constraint). In implementations
     * that support node type registration any value constraint set on BOOLEAN is
     * ignored and discarded.
     *
     * The remaining types all have value constraints in the form of inclusive
     * or exclusive ranges: i.e., "[min, max]", "(min, max)", "(min, max]" or
     * "[min, max)". Where "[" and "]" indicate "inclusive", while "(" and ")"
     * indicate "exclusive". A missing min or max value indicates no bound in
     * that direction. For example [,5] means no minimum but a maximum of 5
     * (inclusive) while [,] means simply that any value will suffice, The meaning
     * of the min and max values themselves differ between types as follows:
     *
     * <b>BINARY</b>: min and max specify the allowed size range of the binary
     * value in bytes.
     *
     * <b>DATE</b>: min and max are dates specifying the allowed date range. The date
     * strings must be in the ISO8601-compliant format: YYYY-MM-DDThh:mm:ss.sssTZD.
     *
     * <b>LONG, DOUBLE</b>: min and max are numbers.
     *
     * In implementations that support node type registration, when specifying
     * that a DATE, LONG or DOUBLE is constrained to be equal to some disjunctive
     * set of constants, a string consisting of just the constant itself, "c" may
     * be used as a shorthand for the standard constraint notation of "[c, c]",
     * where c is the constant. For example, to indicate that particular LONG
     * property is constrained to be one of the values 2, 4, or 8, the constraint
     * string array {"2", "4", "8"} can be used instead of the standard notation,
     * {"[2,2]", "[4,4]", "[8,8]"}. However, even if this shorthand is used on
     * registration, the value returned by
     * PropertyDefinitionInterface::getValueConstraints() will always use the
     * standard notation.
     *
     * Because constraints are returned as an array of disjunctive constraints,
     * in many cases the elements of the array can serve directly as a "choice
     * list". This may, for example, be used by an application to display
     * options to the end user indicating the set of permitted values.
     *
     * In implementations that support node type registration, if this
     * PropertyDefinition object is actually a newly-created empty
     * PropertyDefinitionTemplate, then this method will return null.
     *
     * @return array the value constraint strings
     *
     * @api
     */
    public function getValueConstraints()
    {
        return [];
    }

    /**
     * Gets the default value(s) of the property.
     *
     * These are the values that the property defined by this PropertyDefinition
     * will be assigned if it is automatically created (that is, if
     * ItemDefinitionInterface::isAutoCreated() returns true).
     * This method returns an array of values. If the property is multi-
     * valued, then this array represents the full set of values that the property
     * will be assigned upon being auto-created. Note that this could be the empty
     * array. If the property is single-valued, then the array returned will be
     * of size 1.
     *
     * If null is returned, then the property has no fixed default value. This
     * does not exclude the possibility that the property still assumes some
     * value automatically, but that value may be parametrized (for example, "the
     * current date") and hence not expressible as a single fixed value. In
     * particular, this must be the case if isAutoCreated returns true and this
     * method returns null.
     *
     * Note that to indicate a null value for this attribute in a node type
     * definition that is stored in content, the jcr:defaultValues property is
     * simply removed (since null values for properties are not allowed.
     *
     * In implementations that support node type registration, if this
     * PropertyDefinition object is actually a newly-created empty
     * PropertyDefinitionTemplate, then this method will return null.
     *
     * @return array An array of mixed php values
     *
     * @api
     */
    public function getDefaultValues()
    {
        return [false];
    }

    /**
     * Reports whether this property can have multiple values.
     *
     * <b>Note</b> that the isMultiple flag is special in that a given node
     * type may have two property definitions that are identical in every
     * respect except for the their isMultiple status. For example, a node type
     * can specify two string properties both called X, one of which is
     * multi-valued and the other not. An example of such a node type is
     * nt:unstructured.
     *
     * In implementations that support node type registration, if this
     * PropertyDefinition object is actually a newly-created empty
     * PropertyDefinitionTemplate, then this method will return false.
     *
     * @return bool True, if this property may have multiple values, else
     *              false
     *
     * @api
     */
    public function isMultiple()
    {
        return false;
    }

    /**
     * Returns the set of query comparison operators supported by this
     * property.
     *
     * This attribute only takes effect if the node type holding the property
     * definition has a queryable setting of true.
     *
     * JCR defines the comparison operators
     * \PHPCR\Query\QueryObjectModelConstantsInterface::JCR_OPERATOR_*
     *
     * An implementation may define additional comparison operators.
     *
     * Note that the set of operators that can appear in this attribute may be
     * limited by implementation-specific constraints that differ across
     * property types. For example, some implementations may permit property
     * definitions to provide JCR_OPERATOR_EQUAL_TO and
     * JCR_OPERATOR_NOT_EQUAL_TO as available operators for BINARY properties
     * while others may not.
     *
     * However, in all cases where a JCR-defined operator is potentially
     * available for a given property type, its behavior must conform to the
     * comparison semantics defined in the specification document (see 3.6.5
     * Comparison of Values).
     *
     * @return array an array of query operator constants as defined in
     *               \PHPCR\Query\QueryObjectModelConstantsInterface
     *
     * @see \PHPCR\Query\QueryObjectModelConstantsInterface
     *
     * @api
     */
    public function getAvailableQueryOperators()
    {
        return [];
    }

    /**
     * Determines if this property is full-text searchable.
     *
     * Returns true if this property is full-text searchable,
     * meaning that its value is accessible through the full-text search
     * function within a query.
     *
     * This attribute only takes effect if the node type holding the
     * property definition has a queryable setting of true.
     *
     * @return bool True, if this property is full-text searchable, else false
     *
     * @api
     */
    public function isFullTextSearchable()
    {
        return false;
    }

    /**
     * Report this property is orderable by a query.
     *
     * Returns true if this property is query orderable,
     * meaning that query results may be ordered by this property
     * using the order-by clause of a query.
     *
     * This attribute only takes effect if the node type holding the
     * property definition has a queryable setting of true.
     *
     * @return bool True, if this property is query orderable, else false
     *
     * @api
     */
    public function isQueryOrderable()
    {
        return false;
    }
}
