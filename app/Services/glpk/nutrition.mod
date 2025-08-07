# GLPK Model for Meal Planning Optimization

# Sets
set INGREDIENTS;
set MACROS := 1, 2, 3, 4; # Use integers instead of strings for the set members

# Parameters
param min_grams {INGREDIENTS} >= 0;    # Minimum grams allowed for each ingredient
param max_grams {INGREDIENTS} >= 0;    # Maximum grams allowed for each ingredient (if 0, no limit)
param cost_per_g {INGREDIENTS} >= 0;   # Cost per gram for each ingredient (can be 0 if optimizing for macros only)
param macro_content {INGREDIENTS, MACROS} >= 0; # Macro content per gram for each ingredient, indexed by integer

param target_min {MACROS} >= 0;        # Minimum target for each macro, indexed by integer
param target_max {MACROS} >= 0;        # Maximum target for each macro, indexed by integer

# Variables
var x {INGREDIENTS} >= 0;              # Grams of each ingredient to use
var deviation_low {MACROS} >= 0;       # Deviation below target_min for each macro, indexed by integer
var deviation_high {MACROS} >= 0;      # Deviation above target_max for each macro, indexed by integer

# Objective: Minimize total deviation from macro targets (and optionally cost)
minimize total_deviation:
    sum {m in MACROS} (deviation_low[m] * 1000 + deviation_high[m] * 1000)
    + sum {i in INGREDIENTS} (x[i] * cost_per_g[i]); # Add cost as a secondary objective

# Constraints

# 1. Ingredient Quantity Limits
s.t. ingredient_min {i in INGREDIENTS}:
    x[i] >= min_grams[i];

s.t. ingredient_max {i in INGREDIENTS}:
    x[i] <= max_grams[i];

# 2. Macro Targets with Soft Constraints (deviations)
s.t. macro_target_min {m in MACROS}:
    sum {i in INGREDIENTS} (x[i] * macro_content[i, m]) + deviation_low[m] >= target_min[m];

s.t. macro_target_max {m in MACROS}:
    sum {i in INGREDIENTS} (x[i] * macro_content[i, m]) - deviation_high[m] <= target_max[m];

# Solve and display results
solve;

# Display optimal ingredient amounts
display x;

# Display deviations
display deviation_low, deviation_high;

# Display objective value (total_deviation)
display total_deviation;

end;