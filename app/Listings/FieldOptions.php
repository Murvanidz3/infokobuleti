<?php

namespace App\Listings;

/**
 * Where a field's choices come from. Names follow the wizard's option lists.
 */
enum FieldOptions: string
{
    case Deals = 'deals';
    case PropertyTypes = 'propertyTypes';
    case PropertyConditions = 'propertyConditions';
    case BuildingStatuses = 'buildingStatuses';
    case ListedBy = 'listedBy';
    case PropertyFeatures = 'propertyFeatures';
    case Locations = 'locations';
    case Currencies = 'currencies';
    case ClassifiedParents = 'classifiedParents';
    case ClassifiedCategories = 'classifiedCategories';
    case ItemConditions = 'itemConditions';
    case CategorySchema = 'categorySchema';
    case VacancyCategories = 'vacancyCategories';
    case EmploymentTypes = 'employmentTypes';
    case Experience = 'experience';
    case Languages = 'languages';
    case Benefits = 'benefits';
    case SalaryPeriods = 'salaryPeriods';
    case ApplyMethods = 'applyMethods';
}
