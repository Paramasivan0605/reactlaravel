import React from "react";

export default function FoodItem({ item, currency, onOpen }) {
  return (
    <div className="swiggy-card" onClick={onOpen}>
      <div className="swiggy-card-image">
        <img
          src={item.category_image || "/images/placeholder-food.jpg"}
          alt={item.name}
        />
      </div>

      <div className="swiggy-card-info">
        <h3 className="swiggy-food-name">{item.name}</h3>

        {item.description && (
          <p className="swiggy-food-desc">{item.description.slice(0, 40)}...</p>
        )}

        <div className="swiggy-price-row">
          <span className="swiggy-currency">{currency}</span>
          <span className="swiggy-price">
            {parseFloat(item.price).toFixed(2)}
          </span>
        </div>

        <button className="swiggy-add-btn">ADD +</button>
      </div>
    </div>
  );
}
