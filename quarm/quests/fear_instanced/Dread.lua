--Spawns an iksar broodling on the death of the golems 75% of the time.

function event_signal(e)
	if(e.signal == 1) then
		e.self:Say("Such is the will of Cazic-Thule!");
	end
end

function event_death_complete(e)
	local expansion_flag = eq.get_current_expansion();
	if(expansion_flag >= 2.0 and math.random(100) > 24) then
		eq.spawn2(72105,0,0,e.self:GetX(),e.self:GetY(),e.self:GetZ(),e.self:GetHeading());
	end
end
