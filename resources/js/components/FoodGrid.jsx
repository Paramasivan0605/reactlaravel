import React, { useState } from "react";
import FoodItem from "./FoodItem";
import FoodItemModal from "./FoodItemModal";

export default function FoodGrid({ foods = [], activeCategory, currency }) {
  const [selected, setSelected] = useState(null);

  const visible =
    activeCategory === "all"
      ? foods
      : foods.filter((f) => String(f.category_id) === String(activeCategory));

  return (
    <section className="swiggy-grid-section">
      <h2 className="section-title">Recommended for you</h2>

      {visible.length === 0 ? (
        <div className="empty-state">🍽️ No Menu Items Available</div>
      ) : (
        <div className="swiggy-food-grid">
          {visible.map((item) => (
            <FoodItem
              key={item.id}
              item={item}
              currency={currency}
              onOpen={() => setSelected(item)}
            />
          ))}
        </div>
      )}

      <FoodItemModal
        item={selected}
        onClose={() => setSelected(null)}
        currency={currency}
      />
    </section>
  );
}
