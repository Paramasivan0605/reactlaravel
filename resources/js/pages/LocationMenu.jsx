import React, { useEffect, useState } from 'react';
import axios from 'axios';
import LocationHeader from '../components/LocationHeader';
import DeliveryModal from '../components/DeliveryModal';
import CategoryList from '../components/CategoryList';
import FoodGrid from '../components/FoodGrid';
import Loader from '../components/Loader';
import '../css/location-menu.css';

export default function LocationMenu({ locationId }) {
  const [loading, setLoading] = useState(true);
  const [location, setLocation] = useState(null);
  const [categories, setCategories] = useState([]);
  const [foodMenu, setFoodMenu] = useState([]);
  const [currency, setCurrency] = useState('₹');
  const [activeCategory, setActiveCategory] = useState('all');
  const [deliveryModalOpen, setDeliveryModalOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    loadData();
  }, []);

  async function loadData() {
    try {
      axios.defaults.withCredentials = true;
      const res = await axios.get(`/api/location/${locationId}/menu`);
      setLocation(res.data.location);
      setCategories(res.data.categories || []);
      setFoodMenu(res.data.foodMenu || []);
      setCurrency(res.data.currency || '₹');
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  }

  const filteredFoods = foodMenu.filter(item =>
    item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    item.description?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  if (loading) return <Loader />;

  return (
    <div className="lm-root">
      <DeliveryModal
        open={deliveryModalOpen}
        onClose={() => setDeliveryModalOpen(false)}
        locationId={locationId}
      />

      {/* Header */}
      <LocationHeader
        location={location}
        currency={currency}
        onChooseDelivery={() => setDeliveryModalOpen(true)}
      />

      {/* Search Bar */}
      <div className="lm-search-section">
        <div className="lm-search-container">
          <div className="lm-search-icon">🔍</div>
          <input
            type="text"
            className="lm-search-input"
            placeholder="Search for 'Biryani'"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>
      </div>

      {/* Offers Banner */}
      <div className="lm-offers-banner">
        <div className="lm-offers-scroll">
          <div className="lm-offer-card">
            <div className="lm-offer-badge">EXTRA 15% OFF</div>
            <div className="lm-offer-text">
              Over existing offers with <span className="lm-one-badge">one</span>
            </div>
          </div>
          <div className="lm-offer-card">
            <div className="lm-offer-badge">Get 65% OFF</div>
            <div className="lm-offer-text">On your first order</div>
          </div>
        </div>
      </div>

      {/* Quick Actions - Grid Layout */}
      <div className="lm-quick-actions">
        <div className="lm-actions-grid">
          <button className="lm-action-btn">
            <div className="lm-action-icon">🍽️</div>
            <div className="lm-action-text">Meals</div>
          </button>
          <button className="lm-action-btn">
            <div className="lm-action-icon">🍛</div>
            <div className="lm-action-text">Biryani</div>
          </button>
          <button className="lm-action-btn">
            <div className="lm-action-icon">🍕</div>
            <div className="lm-action-text">Pizza</div>
          </button>
          <button className="lm-action-btn">
            <div className="lm-action-icon">🥗</div>
            <div className="lm-action-text">Healthy</div>
          </button>
          <button className="lm-action-btn">
            <div className="lm-action-icon">🍦</div>
            <div className="lm-action-text">Desserts</div>
          </button>
        </div>
      </div>

      {/* Restaurant Section */}
      <div className="lm-restaurant-section">
        <div className="lm-section-header">
          <h3 className="lm-section-title">Restaurants</h3>
          <a href="#" className="lm-see-all">See All</a>
        </div>
        
        <div className="lm-restaurant-card">
          <div className="lm-restaurant-image">
            <img src="/images/pizza-hut.jpg" alt="Pizza Hut" />
          </div>
          <div className="lm-restaurant-info">
            <h4 className="lm-restaurant-name">Pizza Hut</h4>
            <p className="lm-restaurant-cuisine">Pizza • Fast Food • Italian</p>
            <div className="lm-restaurant-meta">
              <span>30-35 mins</span>
              <span className="lm-rating">4.2 ★</span>
            </div>
            <button className="lm-order-btn">ORDER NOW</button>
          </div>
        </div>
      </div>

      {/* Categories */}
      <div className="lm-categories-wrapper">
        <CategoryList
          categories={categories}
          activeCategory={activeCategory}
          setActiveCategory={setActiveCategory}
        />
      </div>

      {/* Food Grid */}
      <main className="lm-main">
        <FoodGrid
          foods={filteredFoods}
          activeCategory={activeCategory}
          currency={currency}
        />
      </main>

      {/* Bottom Navigation */}
      <div className="lm-bottom-nav">
        <button className="lm-nav-item active">
          <div className="lm-nav-icon">🏠</div>
          <div className="lm-nav-text">Home</div>
        </button>
        <button className="lm-nav-item">
          <div className="lm-nav-icon">🍽️</div>
          <div className="lm-nav-text">Food</div>
        </button>
        <button className="lm-nav-item">
          <div className="lm-nav-icon">⚡</div>
          <div className="lm-nav-text">Bolt</div>
        </button>
        <button className="lm-nav-item">
          <div className="lm-nav-icon">🛒</div>
          <div className="lm-nav-text">Cart</div>
        </button>
        <button className="lm-nav-item">
          <div className="lm-nav-icon">📋</div>
          <div className="lm-nav-text">Reorder</div>
        </button>
      </div>
    </div>
  );
}